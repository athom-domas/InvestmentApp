# Scoring Engine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a six-factor scoring engine that ranks securities and persists ranked results in `model_runs`, `factor_values`, and `security_rankings`.

**Architecture:** FactorCalculator computes per-security raw values with confidence scores; Normalizer applies cross-sectional percentile ranking; ScoringEngine orchestrates the pipeline end-to-end. All "lower is better" inversions happen inside FactorCalculator so the Normalizer always treats its input as "higher is better".

**Tech Stack:** PHP 8.3, Laravel 13, Eloquent ORM, PHPUnit 11, SQLite in-memory (tests)

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `config/investment_scoring.php` | Create | Config: weights, version, thresholds |
| `app/Services/Scoring/DTO/FactorResult.php` | Create | Value object: raw_value, confidence, components |
| `app/Services/Scoring/DTO/SecurityScoreResult.php` | Create | Value object: final_score, normalized scores, explanation |
| `app/Services/Scoring/Normalizer.php` | Create | Percentile ranking + winsorization → 0–100 |
| `app/Services/Scoring/FactorCalculator.php` | Create | Per-security raw factor calculation |
| `app/Services/Scoring/ExplanationBuilder.php` | Create | Plain-language summary + risks |
| `app/Services/Scoring/ScoringEngine.php` | Create | Pipeline orchestration |
| `app/Console/Commands/ScoringRun.php` | Create | `scoring:run` artisan command |
| `tests/Unit/Scoring/NormalizerTest.php` | Create | Pure unit tests (no DB) |
| `tests/Feature/Scoring/FactorCalculatorTest.php` | Create | Feature tests with in-memory SQLite |
| `tests/Feature/Scoring/ScoringEngineTest.php` | Create | Integration tests with in-memory SQLite |
| `database/seeders/DemoSecuritiesSeeder.php` | Modify | 252 price bars, 3 fiscal years, pre-computed ratios |

---

## Task 1: Config

**Files:**
- Create: `config/investment_scoring.php`

- [ ] **Create config file**

```php
<?php
// config/investment_scoring.php
return [
    'model_version' => env('SCORING_MODEL_VERSION', '1.0.0'),

    'default_universe' => 'ALL',

    'factor_weights' => [
        'quality'            => 0.25,
        'value'              => 0.20,
        'momentum'           => 0.20,
        'growth'             => 0.15,
        'financial_strength' => 0.10,
        'risk'               => 0.10,
    ],

    'minimum_price_history_days' => 20,

    'risk_penalty_settings' => [
        'missing_fundamentals_penalty' => 0.30,
        'insufficient_history_penalty' => 0.20,
    ],

    'liquidity_settings' => [
        'min_avg_volume' => 100_000,
    ],

    'score_scale' => [
        'min' => 0,
        'max' => 100,
    ],
];
```

- [ ] **Verify config loads**

```bash
docker compose exec app php artisan tinker --execute="var_dump(config('investment_scoring.factor_weights'));"
```

Expected: array with 6 keys summing to 1.0.

- [ ] **Commit**

```bash
git add config/investment_scoring.php
git commit -m "feat: add investment_scoring config"
```

---

## Task 2: DTOs

**Files:**
- Create: `app/Services/Scoring/DTO/FactorResult.php`
- Create: `app/Services/Scoring/DTO/SecurityScoreResult.php`

- [ ] **Create FactorResult**

```php
<?php
// app/Services/Scoring/DTO/FactorResult.php
namespace App\Services\Scoring\DTO;

readonly class FactorResult
{
    public function __construct(
        public string $factor_code,
        public ?float $raw_value,
        public float  $confidence,
        public array  $components = [],
    ) {}
}
```

- [ ] **Create SecurityScoreResult**

```php
<?php
// app/Services/Scoring/DTO/SecurityScoreResult.php
namespace App\Services\Scoring\DTO;

readonly class SecurityScoreResult
{
    /**
     * @param  FactorResult[]           $factor_results  keyed by factor_code
     * @param  array<string, float>     $normalized      factor_code => 0-100
     */
    public function __construct(
        public int    $security_id,
        public float  $final_score,
        public array  $factor_results,
        public array  $normalized,
        public string $summary,
        public string $risks,
    ) {}
}
```

- [ ] **Commit**

```bash
git add app/Services/Scoring/DTO/
git commit -m "feat: add scoring DTOs"
```

---

## Task 3: Normalizer + Unit Tests

**Files:**
- Create: `app/Services/Scoring/Normalizer.php`
- Create: `tests/Unit/Scoring/NormalizerTest.php`

- [ ] **Write failing tests first**

```php
<?php
// tests/Unit/Scoring/NormalizerTest.php
namespace Tests\Unit\Scoring;

use App\Services\Scoring\Normalizer;
use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    private Normalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new Normalizer();
    }

    public function test_null_values_receive_zero(): void
    {
        $result = $this->normalizer->normalize([1 => null, 2 => 50.0, 3 => 100.0]);
        $this->assertSame(0.0, $result[1]);
    }

    public function test_all_null_returns_zeros(): void
    {
        $result = $this->normalizer->normalize([1 => null, 2 => null]);
        $this->assertSame([1 => 0.0, 2 => 0.0], $result);
    }

    public function test_all_identical_values_return_50(): void
    {
        $result = $this->normalizer->normalize([1 => 10.0, 2 => 10.0, 3 => 10.0]);
        $this->assertSame(50.0, $result[1]);
        $this->assertSame(50.0, $result[3]);
    }

    public function test_scores_bounded_0_to_100(): void
    {
        $values = array_combine(range(1, 10), [5, 3, 99, 1, 50, 2, 80, 10, 20, 0.1]);
        $result = $this->normalizer->normalize($values);
        foreach ($result as $score) {
            $this->assertGreaterThanOrEqual(0.0, $score);
            $this->assertLessThanOrEqual(100.0, $score);
        }
    }

    public function test_highest_value_gets_100_lowest_gets_0(): void
    {
        $result = $this->normalizer->normalize([1 => 10.0, 2 => 50.0, 3 => 100.0]);
        $this->assertSame(100.0, $result[3]);
        $this->assertSame(0.0, $result[1]);
    }

    public function test_lower_is_better_inverts_ranking(): void
    {
        // id=1 has value 10 (lower = better), id=2 has 100
        $result = $this->normalizer->normalize([1 => 10.0, 2 => 100.0], higherIsBetter: false);
        $this->assertGreaterThan($result[2], $result[1]);
    }

    public function test_single_non_null_value_gets_50(): void
    {
        $result = $this->normalizer->normalize([1 => 42.0]);
        $this->assertSame(50.0, $result[1]);
    }

    public function test_empty_input_returns_empty(): void
    {
        $result = $this->normalizer->normalize([]);
        $this->assertSame([], $result);
    }

    public function test_null_mixed_with_valid_values(): void
    {
        $result = $this->normalizer->normalize([1 => null, 2 => 10.0, 3 => 20.0]);
        $this->assertSame(0.0, $result[1]);
        $this->assertSame(0.0, $result[2]);
        $this->assertSame(100.0, $result[3]);
    }
}
```

- [ ] **Run tests to confirm they fail**

```bash
docker compose exec app php artisan test tests/Unit/Scoring/NormalizerTest.php
```

Expected: ERROR — class `App\Services\Scoring\Normalizer` not found.

- [ ] **Implement Normalizer**

```php
<?php
// app/Services/Scoring/Normalizer.php
namespace App\Services\Scoring;

class Normalizer
{
    /**
     * Normalize values to [0, 100] via percentile ranking with winsorization.
     *
     * - null values → 0
     * - all-same values → 50
     * - winsorization at 5th/95th percentile when n ≥ 3
     *
     * @param  array<int|string, float|null>  $values  keyed by security id
     * @param  bool  $higherIsBetter
     * @return array<int|string, float>
     */
    public function normalize(array $values, bool $higherIsBetter = true): array
    {
        if (empty($values)) {
            return [];
        }

        $nullKeys    = array_keys(array_filter($values, fn ($v) => $v === null));
        $nonNullKeys = array_keys(array_filter($values, fn ($v) => $v !== null));
        $nonNullVals = array_map(fn ($k) => (float) $values[$k], $nonNullKeys);

        if (empty($nonNullKeys)) {
            return array_fill_keys(array_keys($values), 0.0);
        }

        $n = count($nonNullVals);

        // Single non-null value → 50
        if ($n === 1) {
            $result = array_fill_keys($nullKeys, 0.0);
            $result[$nonNullKeys[0]] = 50.0;
            return $result;
        }

        // Winsorize when n ≥ 3
        if ($n >= 3) {
            $sorted = $nonNullVals;
            sort($sorted);
            $lower = $sorted[(int) floor(0.05 * ($n - 1))];
            $upper = $sorted[(int) ceil(0.95 * ($n - 1))];
            $nonNullVals = array_map(fn ($v) => max($lower, min($upper, $v)), $nonNullVals);
        }

        $min = min($nonNullVals);
        $max = max($nonNullVals);

        // All identical after winsorization
        if ($min === $max) {
            $result = array_fill_keys($nullKeys, 0.0);
            foreach ($nonNullKeys as $k) {
                $result[$k] = 50.0;
            }
            return $result;
        }

        // Rank ascending; invert if lower is better
        $indexed = array_combine($nonNullKeys, $nonNullVals);
        if ($higherIsBetter) {
            asort($indexed);
        } else {
            arsort($indexed);
        }

        $result  = array_fill_keys($nullKeys, 0.0);
        $i       = 0;
        $nMinus1 = $n - 1;
        foreach ($indexed as $k => $v) {
            $result[$k] = round(($i / $nMinus1) * 100.0, 4);
            $i++;
        }

        return $result;
    }
}
```

- [ ] **Run tests to confirm they pass**

```bash
docker compose exec app php artisan test tests/Unit/Scoring/NormalizerTest.php
```

Expected: 9 tests, 9 assertions, PASS.

- [ ] **Commit**

```bash
git add app/Services/Scoring/Normalizer.php tests/Unit/Scoring/NormalizerTest.php
git commit -m "feat: add Normalizer with percentile ranking and unit tests"
```

---

## Task 4: FactorCalculator + Feature Tests

**Files:**
- Create: `app/Services/Scoring/FactorCalculator.php`
- Create: `tests/Feature/Scoring/FactorCalculatorTest.php`

- [ ] **Write failing feature tests**

```php
<?php
// tests/Feature/Scoring/FactorCalculatorTest.php
namespace Tests\Feature\Scoring;

use App\Models\Fundamental;
use App\Models\PriceBar;
use App\Models\Security;
use App\Services\Scoring\FactorCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactorCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private FactorCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new FactorCalculator();
    }

    // ── Momentum ──────────────────────────────────────────────────────────────

    public function test_momentum_1m_return_with_known_series(): void
    {
        $security = Security::factory()->create();
        $base     = now()->subDays(32);

        // 30 bars at 100, then one bar at 110
        for ($i = 0; $i < 30; $i++) {
            PriceBar::factory()->create([
                'security_id' => $security->id,
                'date'        => $base->copy()->addDays($i)->toDateString(),
                'close'       => 100.0,
                'open' => 100.0, 'high' => 100.0, 'low' => 100.0,
            ]);
        }
        PriceBar::factory()->create([
            'security_id' => $security->id,
            'date'        => $base->copy()->addDays(30)->toDateString(),
            'close'       => 110.0,
            'open' => 110.0, 'high' => 110.0, 'low' => 110.0,
        ]);

        $security->load(['fundamentals', 'priceBars' => fn ($q) => $q->orderBy('date')]);
        $results = $this->calc->calculate($security);

        $this->assertArrayHasKey('return_1m', $results['momentum']->components);
        $this->assertEqualsWithDelta(0.10, $results['momentum']->components['return_1m'], 0.005);
    }

    public function test_momentum_is_null_with_fewer_than_2_bars(): void
    {
        $security = Security::factory()->create();
        $security->load(['fundamentals', 'priceBars']);
        $results = $this->calc->calculate($security);

        $this->assertNull($results['momentum']->raw_value);
        $this->assertSame(0.0, $results['momentum']->confidence);
    }

    // ── Missing fundamentals ──────────────────────────────────────────────────

    public function test_quality_is_null_without_fundamentals(): void
    {
        $security = Security::factory()->create();
        $security->load(['fundamentals', 'priceBars']);
        $results = $this->calc->calculate($security);

        $this->assertNull($results['quality']->raw_value);
        $this->assertSame(0.0, $results['quality']->confidence);
    }

    public function test_growth_is_null_with_single_fundamental_period(): void
    {
        $security = Security::factory()->create();
        Fundamental::factory()->create([
            'security_id'     => $security->id,
            'fiscal_year'     => 2024,
            'fiscal_period'   => 'TTM',
            'period_end_date' => '2024-12-31',
        ]);
        $security->load(['fundamentals', 'priceBars']);
        $results = $this->calc->calculate($security);

        $this->assertNull($results['growth']->raw_value);
        $this->assertSame(0.0, $results['growth']->confidence);
    }

    // ── Growth ────────────────────────────────────────────────────────────────

    public function test_growth_calculated_correctly_with_two_periods(): void
    {
        $security = Security::factory()->create();
        Fundamental::factory()->create([
            'security_id'     => $security->id,
            'fiscal_year'     => 2023,
            'fiscal_period'   => 'FY',
            'period_end_date' => '2023-12-31',
            'revenue'         => 100_000_000,
            'net_income'      => 10_000_000,
            'free_cash_flow'  => 8_000_000,
        ]);
        Fundamental::factory()->create([
            'security_id'     => $security->id,
            'fiscal_year'     => 2024,
            'fiscal_period'   => 'TTM',
            'period_end_date' => '2024-12-31',
            'revenue'         => 120_000_000,  // +20%
            'net_income'      => 12_000_000,   // +20%
            'free_cash_flow'  => 9_600_000,    // +20%
        ]);
        $security->load(['fundamentals', 'priceBars']);
        $results = $this->calc->calculate($security);

        $this->assertGreaterThan(0.0, $results['growth']->confidence);
        $this->assertEqualsWithDelta(0.20, $results['growth']->raw_value, 0.005);
    }

    // ── Risk ──────────────────────────────────────────────────────────────────

    public function test_risk_penalised_when_fundamentals_missing(): void
    {
        // Build two securities: one with fundamentals, one without
        $withFund = Security::factory()->create();
        $noFund   = Security::factory()->create();

        Fundamental::factory()->create(['security_id' => $withFund->id]);

        foreach ([$withFund, $noFund] as $s) {
            for ($i = 0; $i < 25; $i++) {
                PriceBar::factory()->create([
                    'security_id' => $s->id,
                    'date'        => now()->subDays(25 - $i)->toDateString(),
                    'close'       => 100.0,
                    'open' => 100.0, 'high' => 100.0, 'low' => 100.0,
                ]);
            }
        }

        $withFund->load(['fundamentals', 'priceBars' => fn ($q) => $q->orderBy('date')]);
        $noFund->load(['fundamentals', 'priceBars' => fn ($q) => $q->orderBy('date')]);

        $withResult = $this->calc->calculate($withFund);
        $noResult   = $this->calc->calculate($noFund);

        // Security without fundamentals should have lower raw risk score
        $this->assertGreaterThan(
            $noResult['risk']->raw_value ?? PHP_INT_MIN,
            $withResult['risk']->raw_value ?? PHP_INT_MAX
        );
    }
}
```

- [ ] **Run tests to confirm they fail**

```bash
docker compose exec app php artisan test tests/Feature/Scoring/FactorCalculatorTest.php
```

Expected: ERROR — class not found.

- [ ] **Implement FactorCalculator**

```php
<?php
// app/Services/Scoring/FactorCalculator.php
namespace App\Services\Scoring;

use App\Models\Security;
use App\Services\Scoring\DTO\FactorResult;
use Illuminate\Support\Collection;

class FactorCalculator
{
    /**
     * @return array<string, FactorResult>
     */
    public function calculate(Security $security): array
    {
        // Expects priceBars already loaded and sorted ascending by date.
        $fundamentals    = $security->fundamentals->sortByDesc('fiscal_year')->values();
        $current         = $fundamentals->first();
        $prior           = $fundamentals->skip(1)->first();
        $bars            = $security->priceBars->sortBy('date')->values();

        return [
            'quality'            => $this->quality($current),
            'value'              => $this->value($current),
            'growth'             => $this->growth($current, $prior),
            'momentum'           => $this->momentum($bars),
            'financial_strength' => $this->financialStrength($current),
            'risk'               => $this->risk($bars, $current),
        ];
    }

    // ── Quality ───────────────────────────────────────────────────────────────

    private function quality(?object $f): FactorResult
    {
        $components = [];
        $values     = [];

        if ($f) {
            if ($f->return_on_equity !== null && $f->return_on_equity > -1.0) {
                $v = (float) $f->return_on_equity;
                $components['roe'] = $v;
                $values[] = $v;
            }
            if ($f->operating_margin !== null) {
                $v = (float) $f->operating_margin;
                $components['operating_margin'] = $v;
                $values[] = $v;
            }
            if ($f->net_margin !== null) {
                $v = (float) $f->net_margin;
                $components['net_margin'] = $v;
                $values[] = $v;
            }
            if ($f->free_cash_flow !== null && (float) $f->revenue > 0) {
                $v = (float) $f->free_cash_flow / (float) $f->revenue;
                $components['fcf_margin'] = $v;
                $values[] = $v;
            }
        }

        return $this->makeResult('quality', $values, 4, $components);
    }

    // ── Value ─────────────────────────────────────────────────────────────────
    // Ratios stored as originals in $components; negated in $values so that
    // "higher raw_value = cheaper stock" (higher is better for the Normalizer).

    private function value(?object $f): FactorResult
    {
        $components = [];
        $values     = [];

        if ($f) {
            if ($f->pe_ratio !== null && (float) $f->pe_ratio > 0) {
                $v = (float) $f->pe_ratio;
                $components['pe_ratio'] = $v;
                $values[] = -$v;
            }
            if ($f->ev_ebitda !== null && (float) $f->ev_ebitda > 0) {
                $v = (float) $f->ev_ebitda;
                $components['ev_ebitda'] = $v;
                $values[] = -$v;
            }
            if ($f->price_to_sales !== null && (float) $f->price_to_sales > 0) {
                $v = (float) $f->price_to_sales;
                $components['price_to_sales'] = $v;
                $values[] = -$v;
            }
            if ($f->price_to_book !== null && (float) $f->price_to_book > 0) {
                $v = (float) $f->price_to_book;
                $components['price_to_book'] = $v;
                $values[] = -$v;
            }
        }

        return $this->makeResult('value', $values, 4, $components);
    }

    // ── Growth ────────────────────────────────────────────────────────────────

    private function growth(?object $current, ?object $prior): FactorResult
    {
        $components = [];
        $values     = [];

        if ($current && $prior) {
            if ((float) $prior->revenue > 0 && $current->revenue !== null) {
                $g = ((float) $current->revenue - (float) $prior->revenue) / (float) $prior->revenue;
                $components['revenue_growth'] = $g;
                $values[] = $g;
            }
            if ((float) $prior->net_income != 0 && $current->net_income !== null) {
                $g = ((float) $current->net_income - (float) $prior->net_income)
                    / abs((float) $prior->net_income);
                $components['net_income_growth'] = $g;
                $values[] = $g;
            }
            if ((float) $prior->free_cash_flow != 0 && $current->free_cash_flow !== null) {
                $g = ((float) $current->free_cash_flow - (float) $prior->free_cash_flow)
                    / abs((float) $prior->free_cash_flow);
                $components['fcf_growth'] = $g;
                $values[] = $g;
            }
        }

        return $this->makeResult('growth', $values, 3, $components);
    }

    // ── Momentum ──────────────────────────────────────────────────────────────

    private function momentum(Collection $bars): FactorResult
    {
        $n          = $bars->count();
        $components = [];
        $values     = [];

        if ($n < 2) {
            return new FactorResult('momentum', null, 0.0, []);
        }

        $latest  = (float) $bars->last()->close;
        $periods = ['1m' => 21, '3m' => 63, '6m' => 126, '12m' => 252];

        foreach ($periods as $label => $lookback) {
            $idx = $n - $lookback - 1;
            if ($idx >= 0) {
                $past = (float) $bars->get($idx)->close;
                if ($past > 0) {
                    $ret                       = ($latest - $past) / $past;
                    $components["return_{$label}"] = $ret;
                    $values[]                  = $ret;
                }
            }
        }

        return $this->makeResult('momentum', $values, 4, $components);
    }

    // ── Financial Strength ────────────────────────────────────────────────────

    private function financialStrength(?object $f): FactorResult
    {
        $components = [];
        $values     = [];

        if ($f) {
            // Debt-to-equity: transform to 0-1, higher = healthier balance sheet
            if ($f->debt_to_equity !== null && (float) $f->shareholders_equity > 0) {
                $de = max(0.0, (float) $f->debt_to_equity);
                $v  = 1.0 / (1.0 + $de);
                $components['de_inv'] = $v;
                $values[] = $v;
            }
            // Cash coverage ratio (capped at 3x)
            if ((float) $f->total_debt > 0 && $f->cash_and_equivalents !== null) {
                $v = min(3.0, (float) $f->cash_and_equivalents / (float) $f->total_debt) / 3.0;
                $components['cash_coverage'] = $v;
                $values[] = $v;
            }
            // Equity ratio: 1 − (liabilities / assets)
            if ((float) $f->total_assets > 0 && $f->total_liabilities !== null) {
                $v = 1.0 - ((float) $f->total_liabilities / (float) $f->total_assets);
                $components['equity_ratio'] = $v;
                $values[] = max(0.0, $v);
            }
        }

        return $this->makeResult('financial_strength', $values, 3, $components);
    }

    // ── Risk ──────────────────────────────────────────────────────────────────
    // All risk signals inverted so "less risk = higher raw_value" = higher score.

    private function risk(Collection $bars, ?object $f): FactorResult
    {
        $components = [];
        $values     = [];
        $n          = $bars->count();
        $minDays    = (int) config('investment_scoring.minimum_price_history_days', 20);

        if ($n >= 2) {
            $returns = [];
            for ($i = 1; $i < $n; $i++) {
                $prev = (float) $bars->get($i - 1)->close;
                $curr = (float) $bars->get($i)->close;
                if ($prev > 0) {
                    $returns[] = ($curr - $prev) / $prev;
                }
            }

            if (count($returns) > 1) {
                $mean     = array_sum($returns) / count($returns);
                $variance = array_sum(array_map(fn ($r) => ($r - $mean) ** 2, $returns))
                          / (count($returns) - 1);
                $vol      = sqrt($variance * 252);      // annualised
                $components['volatility'] = $vol;
                $values[] = -$vol;                      // invert
            }

            $peak  = (float) $bars->first()->close;
            $maxDd = 0.0;
            foreach ($bars as $bar) {
                $close = (float) $bar->close;
                if ($close > $peak) {
                    $peak = $close;
                }
                $dd    = $peak > 0 ? ($peak - $close) / $peak : 0.0;
                $maxDd = max($maxDd, $dd);
            }
            $components['max_drawdown'] = $maxDd;
            $values[] = -$maxDd;                        // invert
        }

        // Data-quality penalty (inverted: 0 penalty → adds 0, full penalty → subtracts)
        $penalty = 0.0;
        if ($f === null) {
            $penalty += (float) config(
                'investment_scoring.risk_penalty_settings.missing_fundamentals_penalty', 0.30
            );
        }
        if ($n < $minDays) {
            $penalty += (float) config(
                'investment_scoring.risk_penalty_settings.insufficient_history_penalty', 0.20
            );
        }
        $dq = max(0.0, 1.0 - $penalty);
        $components['data_quality'] = $dq;
        $values[] = $dq - 1.0;                          // 0 penalty → 0, full → negative

        return $this->makeResult('risk', $values, 3, $components);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeResult(
        string $code,
        array $values,
        int $totalSubFactors,
        array $components
    ): FactorResult {
        $count      = count($values);
        $confidence = $totalSubFactors > 0 ? $count / $totalSubFactors : 0.0;
        $rawValue   = $count > 0 ? array_sum($values) / $count : null;

        return new FactorResult($code, $rawValue, $confidence, $components);
    }
}
```

- [ ] **Run tests to confirm they pass**

```bash
docker compose exec app php artisan test tests/Feature/Scoring/FactorCalculatorTest.php
```

Expected: 7 tests, PASS.

- [ ] **Commit**

```bash
git add app/Services/Scoring/FactorCalculator.php tests/Feature/Scoring/FactorCalculatorTest.php
git commit -m "feat: add FactorCalculator with tests"
```

---

## Task 5: ExplanationBuilder

**Files:**
- Create: `app/Services/Scoring/ExplanationBuilder.php`

- [ ] **Implement ExplanationBuilder**

```php
<?php
// app/Services/Scoring/ExplanationBuilder.php
namespace App\Services\Scoring;

use App\Models\Security;
use App\Services\Scoring\DTO\FactorResult;

class ExplanationBuilder
{
    private const LABELS = [
        'quality'            => 'profitability',
        'value'              => 'valuation',
        'growth'             => 'growth trajectory',
        'momentum'           => 'price momentum',
        'financial_strength' => 'financial solidity',
        'risk'               => 'risk profile',
    ];

    /**
     * @param  array<string, float>      $normalized  factor_code => 0-100
     * @param  array<string, FactorResult> $factorResults
     * @return array{summary: string, risks: string}
     */
    public function build(Security $security, array $normalized, array $factorResults): array
    {
        arsort($normalized);
        $topCodes    = array_slice(array_keys($normalized), 0, 2);
        $bottomCodes = array_slice(array_keys(array_reverse($normalized, true)), 0, 2);

        $topLabels = array_map(fn ($k) => self::LABELS[$k] ?? $k, $topCodes);
        $summary   = sprintf(
            '%s emerged from the screening with notable characteristics in %s.',
            $security->ticker,
            implode(' and ', $topLabels)
        );

        $risks = [];
        foreach ($bottomCodes as $code) {
            $label   = self::LABELS[$code] ?? $code;
            $score   = $normalized[$code] ?? 0;
            if ($score < 40) {
                $risks[] = "- Weak {$label}: flagged for further review.";
            }
        }
        foreach ($factorResults as $code => $fr) {
            if ($fr->confidence < 0.5 && $fr->confidence >= 0) {
                $label   = self::LABELS[$code] ?? $code;
                $risks[] = "- Limited data available for {$label} assessment.";
            }
        }

        return [
            'summary' => $summary,
            'risks'   => implode("\n", array_unique($risks)) ?: 'No significant risks identified in the available data.',
        ];
    }
}
```

- [ ] **Commit**

```bash
git add app/Services/Scoring/ExplanationBuilder.php
git commit -m "feat: add ExplanationBuilder"
```

---

## Task 6: ScoringEngine + Integration Tests

**Files:**
- Create: `app/Services/Scoring/ScoringEngine.php`
- Create: `tests/Feature/Scoring/ScoringEngineTest.php`

- [ ] **Write failing integration tests**

```php
<?php
// tests/Feature/Scoring/ScoringEngineTest.php
namespace Tests\Feature\Scoring;

use App\Models\Fundamental;
use App\Models\ModelRun;
use App\Models\PriceBar;
use App\Models\Security;
use App\Models\SecurityRanking;
use App\Services\Scoring\ScoringEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringEngineTest extends TestCase
{
    use RefreshDatabase;

    private ScoringEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(ScoringEngine::class);
    }

    // ── ModelRun lifecycle ────────────────────────────────────────────────────

    public function test_model_run_created_with_completed_status(): void
    {
        $this->buildUniverse(3);
        $run = $this->engine->run();

        $this->assertDatabaseHas('model_runs', ['id' => $run->id, 'status' => 'completed']);
        $this->assertNotNull($run->fresh()->finished_at);
    }

    // ── Security rankings ─────────────────────────────────────────────────────

    public function test_rankings_created_for_all_active_securities(): void
    {
        $this->buildUniverse(4);
        $run = $this->engine->run();

        $this->assertSame(4, SecurityRanking::where('model_run_id', $run->id)->count());
    }

    public function test_inactive_securities_excluded(): void
    {
        $this->buildUniverse(3);
        Security::factory()->create(['is_active' => false]);

        $run = $this->engine->run();

        $this->assertSame(3, SecurityRanking::where('model_run_id', $run->id)->count());
    }

    // ── Score range ───────────────────────────────────────────────────────────

    public function test_all_final_scores_between_0_and_100(): void
    {
        $this->buildUniverse(5);
        $run = $this->engine->run();

        SecurityRanking::where('model_run_id', $run->id)->each(function ($r) {
            $this->assertGreaterThanOrEqual(0.0, (float) $r->final_score);
            $this->assertLessThanOrEqual(100.0, (float) $r->final_score);
        });
    }

    // ── Ranks ─────────────────────────────────────────────────────────────────

    public function test_ranks_assigned_sequentially_from_1(): void
    {
        $this->buildUniverse(5);
        $run = $this->engine->run();

        $ranks = SecurityRanking::where('model_run_id', $run->id)
            ->orderBy('rank')
            ->pluck('rank')
            ->toArray();

        $this->assertSame(range(1, 5), $ranks);
    }

    public function test_rank_1_has_highest_final_score(): void
    {
        $this->buildUniverse(5);
        $run = $this->engine->run();

        $first  = SecurityRanking::where('model_run_id', $run->id)->orderBy('rank')->first();
        $others = SecurityRanking::where('model_run_id', $run->id)->where('rank', '>', 1)->get();

        foreach ($others as $other) {
            $this->assertGreaterThanOrEqual((float) $other->final_score, (float) $first->final_score);
        }
    }

    // ── Dry run ───────────────────────────────────────────────────────────────

    public function test_dry_run_does_not_persist_anything(): void
    {
        $this->buildUniverse(3);
        $this->engine->run(['dry_run' => true]);

        $this->assertSame(0, ModelRun::count());
        $this->assertSame(0, SecurityRanking::count());
    }

    // ── Limit option ──────────────────────────────────────────────────────────

    public function test_limit_option_restricts_scored_securities(): void
    {
        $this->buildUniverse(5);
        $run = $this->engine->run(['limit' => 2]);

        $this->assertSame(2, SecurityRanking::where('model_run_id', $run->id)->count());
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function buildUniverse(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $s = Security::factory()->create(['is_active' => true]);

            Fundamental::factory()->create([
                'security_id'     => $s->id,
                'fiscal_year'     => 2023,
                'fiscal_period'   => 'FY',
                'period_end_date' => '2023-12-31',
            ]);
            Fundamental::factory()->create([
                'security_id'     => $s->id,
                'fiscal_year'     => 2024,
                'fiscal_period'   => 'TTM',
                'period_end_date' => '2024-12-31',
            ]);

            for ($j = 0; $j < 30; $j++) {
                PriceBar::factory()->create([
                    'security_id' => $s->id,
                    'date'        => now()->subDays(31 - $j)->toDateString(),
                    'close'       => 100 + rand(-5, 5),
                    'open' => 100.0, 'high' => 105.0, 'low' => 95.0,
                ]);
            }
        }
    }
}
```

- [ ] **Run tests to confirm they fail**

```bash
docker compose exec app php artisan test tests/Feature/Scoring/ScoringEngineTest.php
```

Expected: ERROR — class not found.

- [ ] **Implement ScoringEngine**

```php
<?php
// app/Services/Scoring/ScoringEngine.php
namespace App\Services\Scoring;

use App\Models\FactorValue;
use App\Models\ModelRun;
use App\Models\Security;
use App\Models\SecurityRanking;
use App\Services\Scoring\DTO\SecurityScoreResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScoringEngine
{
    private const FACTOR_CODES = [
        'quality', 'value', 'growth', 'momentum', 'financial_strength', 'risk',
    ];

    public function __construct(
        private readonly FactorCalculator  $factorCalculator,
        private readonly Normalizer        $normalizer,
        private readonly ExplanationBuilder $explanationBuilder,
    ) {}

    /**
     * @param  array{
     *     universe?: string,
     *     model_version?: string|null,
     *     limit?: int|null,
     *     dry_run?: bool,
     * }  $options
     */
    public function run(array $options = []): ModelRun
    {
        $universe     = $options['universe']      ?? config('investment_scoring.default_universe', 'ALL');
        $modelVersion = $options['model_version'] ?? config('investment_scoring.model_version', '1.0.0');
        $limit        = isset($options['limit']) ? (int) $options['limit'] : null;
        $dryRun       = (bool) ($options['dry_run'] ?? false);

        if ($dryRun) {
            return $this->computeOnly($universe, $modelVersion, $limit);
        }

        $modelRun = ModelRun::create([
            'model_version' => $modelVersion,
            'universe'      => $universe,
            'data_cutoff_at' => now(),
            'config_hash'   => md5(json_encode(config('investment_scoring'))),
            'status'        => 'running',
            'started_at'    => now(),
        ]);

        try {
            $this->execute($modelRun, $universe, $limit);
            $modelRun->update(['status' => 'completed', 'finished_at' => now()]);
        } catch (\Throwable $e) {
            $modelRun->update(['status' => 'failed', 'finished_at' => now()]);
            Log::error("Scoring run #{$modelRun->id} failed: " . $e->getMessage());
            throw $e;
        }

        return $modelRun->fresh();
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function execute(ModelRun $modelRun, string $universe, ?int $limit): void
    {
        $securities = $this->loadSecurities($universe, $limit);

        if ($securities->isEmpty()) {
            Log::warning("Scoring run #{$modelRun->id}: no active securities found.");
            return;
        }

        // Phase 1: calculate per-security raw factor values
        $allResults = [];   // int(security_id) => array<string, FactorResult>
        foreach ($securities as $security) {
            try {
                $allResults[$security->id] = $this->factorCalculator->calculate($security);
            } catch (\Throwable $e) {
                Log::warning("Scoring: skipping security #{$security->id} ({$security->ticker}): {$e->getMessage()}");
            }
        }

        // Phase 2: cross-sectional normalization per factor
        $weights       = config('investment_scoring.factor_weights');
        $normalizedAll = [];  // factor_code => [security_id => 0-100]

        foreach (self::FACTOR_CODES as $code) {
            $rawValues = [];
            foreach ($allResults as $sid => $results) {
                $rawValues[$sid] = $results[$code]->raw_value ?? null;
            }
            $normalizedAll[$code] = $this->normalizer->normalize($rawValues);
        }

        // Phase 3: compute final scores + explanations
        $scoreResults = [];   // security_id => SecurityScoreResult
        foreach ($allResults as $sid => $factorResults) {
            $normalized = [];
            foreach (self::FACTOR_CODES as $code) {
                $normalized[$code] = $normalizedAll[$code][$sid] ?? 0.0;
            }

            $finalScore = 0.0;
            foreach ($weights as $code => $weight) {
                $finalScore += ($normalized[$code] ?? 0.0) * $weight;
            }

            $security = $securities->firstWhere('id', $sid);
            $explanation = $this->explanationBuilder->build($security, $normalized, $factorResults);

            $scoreResults[$sid] = new SecurityScoreResult(
                security_id:    $sid,
                final_score:    round($finalScore, 4),
                factor_results: $factorResults,
                normalized:     $normalized,
                summary:        $explanation['summary'],
                risks:          $explanation['risks'],
            );
        }

        // Phase 4: persist atomically
        DB::transaction(function () use ($modelRun, $scoreResults, $allResults, $normalizedAll) {
            $now = now();

            // factor_values
            $fvRows = [];
            foreach ($scoreResults as $sid => $sr) {
                foreach ($sr->factor_results as $code => $fr) {
                    $fvRows[] = [
                        'model_run_id'      => $modelRun->id,
                        'security_id'       => $sid,
                        'factor_code'       => $code,
                        'raw_value'         => $fr->raw_value,
                        'normalized_value'  => $normalizedAll[$code][$sid] ?? null,
                        'score'             => $normalizedAll[$code][$sid] ?? 0.0,
                        'explanation'       => json_encode($fr->components),
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
            }
            DB::table('factor_values')->insert($fvRows);

            // security_rankings (sorted by final_score desc → rank 1 = best)
            $sorted = collect($scoreResults)->sortByDesc('final_score')->values();
            $rank   = 1;
            $srRows = [];
            foreach ($sorted as $sr) {
                $n = $sr->normalized;
                $srRows[] = [
                    'model_run_id'             => $modelRun->id,
                    'security_id'              => $sr->security_id,
                    'final_score'              => $sr->final_score,
                    'rank'                     => $rank++,
                    'quality_score'            => $n['quality']            ?? null,
                    'value_score'              => $n['value']              ?? null,
                    'growth_score'             => $n['growth']             ?? null,
                    'momentum_score'           => $n['momentum']           ?? null,
                    'financial_strength_score' => $n['financial_strength'] ?? null,
                    'risk_score'               => $n['risk']               ?? null,
                    'summary'                  => $sr->summary,
                    'risks'                    => $sr->risks,
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ];
            }
            DB::table('security_rankings')->insert($srRows);
        });
    }

    private function computeOnly(string $universe, string $modelVersion, ?int $limit): ModelRun
    {
        $securities = $this->loadSecurities($universe, $limit);
        Log::info("[DRY RUN] Would score {$securities->count()} securities (universe: {$universe}, v{$modelVersion}).");
        foreach ($securities as $security) {
            Log::info("[DRY RUN] {$security->ticker}");
        }
        // Return an unsaved stub so callers can inspect the run metadata
        return new ModelRun([
            'model_version' => $modelVersion,
            'universe'      => $universe,
            'status'        => 'dry_run',
        ]);
    }

    private function loadSecurities(string $universe, ?int $limit)
    {
        $query = Security::where('is_active', true)
            ->with([
                'fundamentals' => fn ($q) => $q->orderByDesc('fiscal_year'),
                'priceBars'    => fn ($q) => $q->orderBy('date'),
            ]);

        if ($universe !== 'ALL') {
            $query->whereHas('exchange', fn ($q) => $q->where('code', $universe));
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
```

- [ ] **Run all scoring tests**

```bash
docker compose exec app php artisan test tests/Feature/Scoring/ tests/Unit/Scoring/
```

Expected: all tests PASS.

- [ ] **Commit**

```bash
git add app/Services/Scoring/ScoringEngine.php tests/Feature/Scoring/ScoringEngineTest.php
git commit -m "feat: add ScoringEngine with integration tests"
```

---

## Task 7: Artisan Command

**Files:**
- Create: `app/Console/Commands/ScoringRun.php`

- [ ] **Implement command**

```php
<?php
// app/Console/Commands/ScoringRun.php
namespace App\Console\Commands;

use App\Services\Scoring\ScoringEngine;
use Illuminate\Console\Command;

class ScoringRun extends Command
{
    protected $signature = 'scoring:run
        {--universe=ALL    : Exchange code filter (NASDAQ, NYSE, MIL) or ALL}
        {--model-version=  : Override config model version}
        {--limit=          : Score only the first N active securities}
        {--dry-run         : Compute without persisting to database}';

    protected $description = 'Run the scoring engine and compute security rankings';

    public function handle(ScoringEngine $engine): int
    {
        $options = [
            'universe'      => $this->option('universe'),
            'model_version' => $this->option('model-version') ?: null,
            'limit'         => $this->option('limit') ? (int) $this->option('limit') : null,
            'dry_run'       => (bool) $this->option('dry-run'),
        ];

        $this->info(sprintf(
            'Starting scoring run [universe=%s, version=%s%s]…',
            $options['universe'],
            $options['model_version'] ?? config('investment_scoring.model_version'),
            $options['dry_run'] ? ', DRY RUN' : ''
        ));

        $run = $engine->run($options);

        if ($options['dry_run']) {
            $this->info('[DRY RUN] Scoring computed — nothing persisted.');
        } else {
            $rankCount = $run->rankings()->count();
            $this->info("ModelRun #{$run->id} completed — {$rankCount} securities ranked.");
        }

        return self::SUCCESS;
    }
}
```

- [ ] **Verify command is registered**

```bash
docker compose exec app php artisan scoring:run --help
```

Expected: shows signature with all four options.

- [ ] **Smoke-test dry-run**

```bash
docker compose exec app php artisan scoring:run --dry-run --limit=3
```

Expected: "DRY RUN" message, no DB writes.

- [ ] **Commit**

```bash
git add app/Console/Commands/ScoringRun.php
git commit -m "feat: add scoring:run artisan command"
```

---

## Task 8: Update DemoSecuritiesSeeder

**Files:**
- Modify: `database/seeders/DemoSecuritiesSeeder.php`

- [ ] **Replace DemoSecuritiesSeeder with extended version**

Replace the full file content with the version below. Key changes:
- `createPriceBars`: generates 252 trading days instead of 30.
- `createFundamental`: now accepts `$multiplier` and creates FY2022 (×0.70), FY2023 (×0.85), TTM2024 (×1.0).
- Added `pe_ratio`, `ev_ebitda`, `price_to_sales`, `price_to_book`, `eps`, `shares_outstanding` computation.

```php
<?php
// database/seeders/DemoSecuritiesSeeder.php
namespace Database\Seeders;

use App\Models\Exchange;
use App\Models\Fundamental;
use App\Models\Industry;
use App\Models\ModelRun;
use App\Models\PriceBar;
use App\Models\Sector;
use App\Models\Security;
use App\Models\SecurityRanking;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSecuritiesSeeder extends Seeder
{
    public function run(): void
    {
        $nasdaq = Exchange::where('code', 'NASDAQ')->firstOrFail();
        $nyse   = Exchange::where('code', 'NYSE')->firstOrFail();
        $mil    = Exchange::where('code', 'MIL')->firstOrFail();

        $sectors    = Sector::all()->keyBy('name');
        $industries = Industry::all()->keyBy('name');

        $securities = [
            ['ticker' => 'AAPL',  'name' => 'Apple Inc.',           'exchange' => $nasdaq, 'sector' => 'Technology',              'industry' => 'Consumer Electronics', 'currency' => 'USD', 'country' => 'US', 'market_cap' => 2_900_000_000_000, 'base_price' => 185.00, 'revenue' => 385_700_000_000, 'net_income' => 97_000_000_000,  'gross_margin' => 0.4530, 'net_margin' => 0.2515, 'total_assets' => 352_600_000_000, 'equity' => 62_100_000_000,  'debt' => 111_100_000_000, 'ebitda' => 123_000_000_000, 'fcf' => 99_000_000_000],
            ['ticker' => 'MSFT',  'name' => 'Microsoft Corp.',       'exchange' => $nasdaq, 'sector' => 'Technology',              'industry' => 'Software',             'currency' => 'USD', 'country' => 'US', 'market_cap' => 3_100_000_000_000, 'base_price' => 415.00, 'revenue' => 211_900_000_000, 'net_income' => 72_400_000_000,  'gross_margin' => 0.6993, 'net_margin' => 0.3416, 'total_assets' => 411_900_000_000, 'equity' => 206_200_000_000, 'debt' => 79_400_000_000,  'ebitda' => 100_000_000_000, 'fcf' => 74_000_000_000],
            ['ticker' => 'NVDA',  'name' => 'NVIDIA Corp.',          'exchange' => $nasdaq, 'sector' => 'Technology',              'industry' => 'Semiconductors',       'currency' => 'USD', 'country' => 'US', 'market_cap' => 2_200_000_000_000, 'base_price' => 875.00, 'revenue' => 60_900_000_000,  'net_income' => 29_800_000_000,  'gross_margin' => 0.7256, 'net_margin' => 0.4893, 'total_assets' => 65_700_000_000,  'equity' => 42_900_000_000,  'debt' => 8_500_000_000,   'ebitda' => 35_000_000_000,  'fcf' => 27_000_000_000],
            ['ticker' => 'CSCO',  'name' => 'Cisco Systems Inc.',    'exchange' => $nasdaq, 'sector' => 'Technology',              'industry' => 'Networking',           'currency' => 'USD', 'country' => 'US', 'market_cap' => 197_000_000_000,   'base_price' => 49.00,  'revenue' => 53_800_000_000,  'net_income' => 11_800_000_000,  'gross_margin' => 0.6320, 'net_margin' => 0.2193, 'total_assets' => 101_900_000_000, 'equity' => 39_700_000_000,  'debt' => 28_600_000_000,  'ebitda' => 16_500_000_000,  'fcf' => 13_000_000_000],
            ['ticker' => 'GOOGL', 'name' => 'Alphabet Inc.',         'exchange' => $nasdaq, 'sector' => 'Communication Services', 'industry' => 'Interactive Media',    'currency' => 'USD', 'country' => 'US', 'market_cap' => 2_100_000_000_000, 'base_price' => 170.00, 'revenue' => 307_400_000_000, 'net_income' => 73_800_000_000,  'gross_margin' => 0.5679, 'net_margin' => 0.2401, 'total_assets' => 402_400_000_000, 'equity' => 292_000_000_000, 'debt' => 13_200_000_000,  'ebitda' => 95_000_000_000,  'fcf' => 69_000_000_000],
            ['ticker' => 'META',  'name' => 'Meta Platforms Inc.',   'exchange' => $nasdaq, 'sector' => 'Communication Services', 'industry' => 'Social Media',         'currency' => 'USD', 'country' => 'US', 'market_cap' => 1_300_000_000_000, 'base_price' => 505.00, 'revenue' => 134_900_000_000, 'net_income' => 39_100_000_000,  'gross_margin' => 0.8099, 'net_margin' => 0.2899, 'total_assets' => 229_600_000_000, 'equity' => 153_200_000_000, 'debt' => 18_400_000_000,  'ebitda' => 58_000_000_000,  'fcf' => 43_000_000_000],
            ['ticker' => 'AMZN',  'name' => 'Amazon.com Inc.',       'exchange' => $nasdaq, 'sector' => 'Consumer Cyclical',      'industry' => 'E-Commerce',           'currency' => 'USD', 'country' => 'US', 'market_cap' => 1_900_000_000_000, 'base_price' => 185.00, 'revenue' => 574_800_000_000, 'net_income' => 30_400_000_000,  'gross_margin' => 0.4700, 'net_margin' => 0.0529, 'total_assets' => 527_900_000_000, 'equity' => 201_900_000_000, 'debt' => 140_100_000_000, 'ebitda' => 85_000_000_000,  'fcf' => 36_000_000_000],
            ['ticker' => 'JPM',   'name' => 'JPMorgan Chase & Co.',  'exchange' => $nyse,   'sector' => 'Financials',             'industry' => 'Banking',              'currency' => 'USD', 'country' => 'US', 'market_cap' => 580_000_000_000,   'base_price' => 200.00, 'revenue' => 162_400_000_000, 'net_income' => 49_600_000_000,  'gross_margin' => 0.6100, 'net_margin' => 0.3054, 'total_assets' => 3_875_000_000_000,'equity' => 329_000_000_000, 'debt' => 512_000_000_000, 'ebitda' => 68_000_000_000,  'fcf' => 45_000_000_000],
            ['ticker' => 'V',     'name' => 'Visa Inc.',             'exchange' => $nyse,   'sector' => 'Financials',             'industry' => 'Financial Services',   'currency' => 'USD', 'country' => 'US', 'market_cap' => 556_000_000_000,   'base_price' => 275.00, 'revenue' => 33_100_000_000,  'net_income' => 17_300_000_000,  'gross_margin' => 0.7970, 'net_margin' => 0.5227, 'total_assets' => 91_800_000_000,  'equity' => 38_800_000_000,  'debt' => 16_500_000_000,  'ebitda' => 25_000_000_000,  'fcf' => 19_000_000_000],
            ['ticker' => 'MA',    'name' => 'Mastercard Inc.',       'exchange' => $nyse,   'sector' => 'Financials',             'industry' => 'Financial Services',   'currency' => 'USD', 'country' => 'US', 'market_cap' => 436_000_000_000,   'base_price' => 470.00, 'revenue' => 25_100_000_000,  'net_income' => 11_200_000_000,  'gross_margin' => 0.7620, 'net_margin' => 0.4462, 'total_assets' => 41_900_000_000,  'equity' => 8_100_000_000,   'debt' => 15_600_000_000,  'ebitda' => 15_000_000_000,  'fcf' => 11_500_000_000],
            ['ticker' => 'JNJ',   'name' => 'Johnson & Johnson',     'exchange' => $nyse,   'sector' => 'Healthcare',             'industry' => 'Pharmaceuticals',      'currency' => 'USD', 'country' => 'US', 'market_cap' => 373_000_000_000,   'base_price' => 155.00, 'revenue' => 85_200_000_000,  'net_income' => 13_800_000_000,  'gross_margin' => 0.6872, 'net_margin' => 0.1620, 'total_assets' => 167_600_000_000, 'equity' => 68_800_000_000,  'debt' => 35_400_000_000,  'ebitda' => 22_000_000_000,  'fcf' => 14_500_000_000],
            ['ticker' => 'UNH',   'name' => 'UnitedHealth Group',    'exchange' => $nyse,   'sector' => 'Healthcare',             'industry' => 'Health Insurance',     'currency' => 'USD', 'country' => 'US', 'market_cap' => 482_000_000_000,   'base_price' => 510.00, 'revenue' => 366_000_000_000, 'net_income' => 22_400_000_000,  'gross_margin' => 0.2400, 'net_margin' => 0.0612, 'total_assets' => 273_700_000_000, 'equity' => 93_400_000_000,  'debt' => 55_700_000_000,  'ebitda' => 30_000_000_000,  'fcf' => 22_000_000_000],
            ['ticker' => 'PFE',   'name' => 'Pfizer Inc.',           'exchange' => $nyse,   'sector' => 'Healthcare',             'industry' => 'Pharmaceuticals',      'currency' => 'USD', 'country' => 'US', 'market_cap' => 158_000_000_000,   'base_price' => 28.00,  'revenue' => 58_500_000_000,  'net_income' => 2_100_000_000,   'gross_margin' => 0.6630, 'net_margin' => 0.0359, 'total_assets' => 226_500_000_000, 'equity' => 96_900_000_000,  'debt' => 62_000_000_000,  'ebitda' => 8_000_000_000,   'fcf' => 5_000_000_000],
            ['ticker' => 'PG',    'name' => 'Procter & Gamble Co.',  'exchange' => $nyse,   'sector' => 'Consumer Defensive',    'industry' => 'Household Products',   'currency' => 'USD', 'country' => 'US', 'market_cap' => 378_000_000_000,   'base_price' => 160.00, 'revenue' => 84_000_000_000,  'net_income' => 14_800_000_000,  'gross_margin' => 0.5000, 'net_margin' => 0.1762, 'total_assets' => 120_200_000_000, 'equity' => 47_100_000_000,  'debt' => 35_700_000_000,  'ebitda' => 20_000_000_000,  'fcf' => 14_000_000_000],
            ['ticker' => 'KO',    'name' => 'The Coca-Cola Co.',     'exchange' => $nyse,   'sector' => 'Consumer Defensive',    'industry' => 'Beverages',            'currency' => 'USD', 'country' => 'US', 'market_cap' => 267_000_000_000,   'base_price' => 62.00,  'revenue' => 45_800_000_000,  'net_income' => 10_700_000_000,  'gross_margin' => 0.5980, 'net_margin' => 0.2358, 'total_assets' => 97_700_000_000,  'equity' => 26_400_000_000,  'debt' => 35_500_000_000,  'ebitda' => 14_000_000_000,  'fcf' => 10_500_000_000],
            ['ticker' => 'HD',    'name' => 'The Home Depot Inc.',   'exchange' => $nyse,   'sector' => 'Consumer Cyclical',     'industry' => 'Home Improvement',     'currency' => 'USD', 'country' => 'US', 'market_cap' => 337_000_000_000,   'base_price' => 340.00, 'revenue' => 153_000_000_000, 'net_income' => 15_100_000_000,  'gross_margin' => 0.3340, 'net_margin' => 0.0987, 'total_assets' => 76_500_000_000,  'equity' => -1_700_000_000,  'debt' => 42_700_000_000,  'ebitda' => 22_000_000_000,  'fcf' => 15_000_000_000],
            ['ticker' => 'DIS',   'name' => 'The Walt Disney Co.',   'exchange' => $nyse,   'sector' => 'Communication Services','industry' => 'Entertainment',        'currency' => 'USD', 'country' => 'US', 'market_cap' => 206_000_000_000,   'base_price' => 113.00, 'revenue' => 88_900_000_000,  'net_income' => 2_400_000_000,   'gross_margin' => 0.3600, 'net_margin' => 0.0270, 'total_assets' => 202_600_000_000, 'equity' => 101_300_000_000, 'debt' => 47_100_000_000,  'ebitda' => 12_000_000_000,  'fcf' => 5_000_000_000],
            ['ticker' => 'XOM',   'name' => 'Exxon Mobil Corp.',     'exchange' => $nyse,   'sector' => 'Energy',                'industry' => 'Oil & Gas',            'currency' => 'USD', 'country' => 'US', 'market_cap' => 432_000_000_000,   'base_price' => 108.00, 'revenue' => 398_700_000_000, 'net_income' => 36_000_000_000,  'gross_margin' => 0.3200, 'net_margin' => 0.0903, 'total_assets' => 376_300_000_000, 'equity' => 168_600_000_000, 'debt' => 40_600_000_000,  'ebitda' => 55_000_000_000,  'fcf' => 35_000_000_000],
            ['ticker' => 'ENI',   'name' => 'Eni S.p.A.',            'exchange' => $mil,    'sector' => 'Energy',                'industry' => 'Oil & Gas',            'currency' => 'EUR', 'country' => 'IT', 'market_cap' => 51_000_000_000,    'base_price' => 14.50,  'revenue' => 94_000_000_000,  'net_income' => 5_800_000_000,   'gross_margin' => 0.2800, 'net_margin' => 0.0617, 'total_assets' => 121_000_000_000, 'equity' => 53_600_000_000,  'debt' => 18_200_000_000,  'ebitda' => 14_000_000_000,  'fcf' => 8_000_000_000],
            ['ticker' => 'ENEL',  'name' => 'Enel S.p.A.',           'exchange' => $mil,    'sector' => 'Utilities',             'industry' => 'Electric Utilities',   'currency' => 'EUR', 'country' => 'IT', 'market_cap' => 63_000_000_000,    'base_price' => 6.20,   'revenue' => 92_900_000_000,  'net_income' => 1_800_000_000,   'gross_margin' => 0.2100, 'net_margin' => 0.0194, 'total_assets' => 185_900_000_000, 'equity' => 56_400_000_000,  'debt' => 63_200_000_000,  'ebitda' => 10_000_000_000,  'fcf' => 4_000_000_000],
            ['ticker' => 'UCG',   'name' => 'UniCredit S.p.A.',      'exchange' => $mil,    'sector' => 'Financials',            'industry' => 'Banking',              'currency' => 'EUR', 'country' => 'IT', 'market_cap' => 65_000_000_000,    'base_price' => 35.50,  'revenue' => 22_700_000_000,  'net_income' => 8_600_000_000,   'gross_margin' => 0.7200, 'net_margin' => 0.3788, 'total_assets' => 728_000_000_000, 'equity' => 69_700_000_000,  'debt' => 85_000_000_000,  'ebitda' => 12_000_000_000,  'fcf' => 9_000_000_000],
            ['ticker' => 'STLAM', 'name' => 'Stellantis N.V.',       'exchange' => $mil,    'sector' => 'Consumer Cyclical',     'industry' => 'Automobiles',          'currency' => 'EUR', 'country' => 'IT', 'market_cap' => 53_000_000_000,    'base_price' => 17.00,  'revenue' => 189_500_000_000, 'net_income' => 18_600_000_000,  'gross_margin' => 0.1950, 'net_margin' => 0.0981, 'total_assets' => 127_000_000_000, 'equity' => 55_200_000_000,  'debt' => 15_800_000_000,  'ebitda' => 25_000_000_000,  'fcf' => 16_000_000_000],
        ];

        $created = [];
        foreach ($securities as $data) {
            $sector   = $sectors->get($data['sector']);
            $industry = $industries->get($data['industry']);

            $security = Security::firstOrCreate(
                ['ticker' => $data['ticker'], 'exchange_id' => $data['exchange']->id],
                [
                    'name'        => $data['name'],
                    'exchange_id' => $data['exchange']->id,
                    'sector_id'   => $sector?->id,
                    'industry_id' => $industry?->id,
                    'currency'    => $data['currency'],
                    'country'     => $data['country'],
                    'market_cap'  => $data['market_cap'],
                    'is_active'   => true,
                    'metadata'    => ['demo' => true],
                ]
            );

            $this->seedPriceBars($security, $data['base_price']);
            $this->seedFundamentals($security, $data);

            $created[] = $security;
        }

        $this->seedDemoModelRun($created);
    }

    // ── Price bars ────────────────────────────────────────────────────────────

    private function seedPriceBars(Security $security, float $basePrice): void
    {
        $tradingDays = $this->lastTradingDays(252);
        $price       = $basePrice;
        $rows        = [];
        $now         = now();

        foreach ($tradingDays as $date) {
            $change = rand(-300, 300) / 10000;
            $close  = round($price * (1 + $change), 6);
            $open   = round($price * (1 + rand(-100, 100) / 10000), 6);
            $high   = round(max($close, $open) * (1 + rand(0, 100) / 10000), 6);
            $low    = round(min($close, $open) * (1 - rand(0, 100) / 10000), 6);

            $rows[] = [
                'security_id'    => $security->id,
                'date'           => $date->toDateString(),
                'open'           => $open,
                'high'           => $high,
                'low'            => $low,
                'close'          => $close,
                'adjusted_close' => $close,
                'volume'         => rand(500_000, 80_000_000),
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            $price = $close;
        }

        DB::table('price_bars')->insertOrIgnore($rows);
    }

    // ── Fundamentals ──────────────────────────────────────────────────────────

    private function seedFundamentals(Security $security, array $data): void
    {
        $periods = [
            ['period' => 'FY',  'year' => 2022, 'multiplier' => 0.70, 'end' => '2022-12-31'],
            ['period' => 'FY',  'year' => 2023, 'multiplier' => 0.85, 'end' => '2023-12-31'],
            ['period' => 'TTM', 'year' => 2024, 'multiplier' => 1.00, 'end' => '2024-12-31'],
        ];

        foreach ($periods as $p) {
            $m          = $p['multiplier'];
            $revenue    = round($data['revenue'] * $m, 2);
            $netIncome  = round($data['net_income'] * $m, 2);
            $ebitda     = round($data['ebitda'] * $m, 2);
            $fcf        = round($data['fcf'] * $m, 2);
            $equity     = $data['equity'] > 0 ? round($data['equity'] * $m, 2) : $data['equity'];
            $debt       = round($data['debt'] * $m, 2);
            $assets     = round($data['total_assets'] * $m, 2);
            $liabilities = round(($assets - $equity), 2);
            $cash       = round($assets * 0.10, 2);
            $shares     = $data['market_cap'] / $data['base_price'];

            // Pre-computed valuation ratios (using current market_cap as approximation)
            $mktCap    = $data['market_cap'];
            $ev        = $mktCap + $debt - $cash;
            $peRatio   = $netIncome > 0 ? round($mktCap / $netIncome, 6) : null;
            $evEbitda  = $ebitda > 0   ? round($ev / $ebitda, 6) : null;
            $pts       = $revenue > 0  ? round($mktCap / $revenue, 6) : null;
            $ptb       = $equity > 0   ? round($mktCap / $equity, 6) : null;
            $epsVal    = $shares > 0   ? round($netIncome / $shares, 6) : null;

            Fundamental::firstOrCreate(
                [
                    'security_id'     => $security->id,
                    'fiscal_period'   => $p['period'],
                    'fiscal_year'     => $p['year'],
                    'period_end_date' => $p['end'],
                ],
                [
                    'revenue'              => $revenue,
                    'gross_profit'         => round($revenue * $data['gross_margin'], 2),
                    'operating_income'     => round($revenue * $data['gross_margin'] * 0.70, 2),
                    'net_income'           => $netIncome,
                    'ebitda'               => $ebitda,
                    'free_cash_flow'       => $fcf,
                    'total_assets'         => $assets,
                    'total_liabilities'    => max(0, $liabilities),
                    'total_debt'           => $debt,
                    'cash_and_equivalents' => $cash,
                    'shareholders_equity'  => $equity > 0 ? $equity : null,
                    'shares_outstanding'   => round($shares, 2),
                    'eps'                  => $epsVal,
                    'pe_ratio'             => $peRatio,
                    'ev_ebitda'            => $evEbitda,
                    'price_to_sales'       => $pts,
                    'price_to_book'        => $ptb,
                    'gross_margin'         => $data['gross_margin'],
                    'operating_margin'     => round($data['gross_margin'] * 0.70, 6),
                    'net_margin'           => $data['net_margin'],
                    'return_on_equity'     => $equity > 0 ? round($netIncome / $equity, 6) : null,
                    'return_on_assets'     => round($netIncome / $assets, 6),
                    'debt_to_equity'       => $equity > 0 ? round($debt / $equity, 6) : null,
                    'metadata'             => ['demo' => true],
                ]
            );
        }
    }

    // ── Demo model run ────────────────────────────────────────────────────────

    private function seedDemoModelRun(array $securities): void
    {
        $run = ModelRun::create([
            'model_version'  => '1.0.0',
            'universe'       => 'DEMO',
            'data_cutoff_at' => now(),
            'config_hash'    => md5('demo-v1'),
            'status'         => 'completed',
            'started_at'     => now()->subMinutes(2),
            'finished_at'    => now(),
            'metadata'       => ['demo' => true],
        ]);

        $rows = [];
        foreach ($securities as $i => $security) {
            $q = round(rand(3000, 9500) / 100, 4);
            $v = round(rand(3000, 9500) / 100, 4);
            $g = round(rand(3000, 9500) / 100, 4);
            $m = round(rand(3000, 9500) / 100, 4);
            $f = round(rand(3000, 9500) / 100, 4);
            $r = round(rand(3000, 9500) / 100, 4);

            $final = round($q * 0.25 + $v * 0.20 + $m * 0.20 + $g * 0.15 + $f * 0.10 + $r * 0.10, 4);

            $rows[] = [
                'model_run_id'             => $run->id,
                'security_id'              => $security->id,
                'final_score'              => $final,
                'rank'                     => null,
                'quality_score'            => $q,
                'value_score'              => $v,
                'growth_score'             => $g,
                'momentum_score'           => $m,
                'financial_strength_score' => $f,
                'risk_score'               => $r,
                'metadata'                 => json_encode(['demo' => true]),
                'created_at'               => now(),
                'updated_at'               => now(),
            ];
        }

        DB::table('security_rankings')->insert($rows);

        $rank = 1;
        SecurityRanking::where('model_run_id', $run->id)
            ->orderByDesc('final_score')
            ->each(fn ($r) => $r->update(['rank' => $rank++]));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function lastTradingDays(int $count): array
    {
        $days = [];
        $date = Carbon::today()->subDay();
        while (count($days) < $count) {
            if (! $date->isWeekend()) {
                $days[] = $date->copy();
            }
            $date->subDay();
        }
        return array_reverse($days);
    }
}
```

- [ ] **Run migrate:fresh --seed and verify**

```bash
docker compose exec app php artisan migrate:fresh --seed
```

```bash
docker compose exec app php artisan tinker --execute="
echo 'PriceBars: ' . \App\Models\PriceBar::count() . PHP_EOL;
echo 'Fundamentals: ' . \App\Models\Fundamental::count() . PHP_EOL;
echo 'Ratios populated: ' . \App\Models\Fundamental::whereNotNull('pe_ratio')->count() . PHP_EOL;
"
```

Expected: PriceBars ≈ 5544 (22 × 252), Fundamentals = 66 (22 × 3), Ratios ≈ 59 (those with positive net_income).

- [ ] **Commit**

```bash
git add database/seeders/DemoSecuritiesSeeder.php
git commit -m "feat: extend demo seeder to 252 bars and 3 fiscal years with pre-computed ratios"
```

---

## Task 9: Full Test Run + Live Smoke Test

- [ ] **Run all scoring tests**

```bash
docker compose exec app php artisan test tests/Unit/Scoring/ tests/Feature/Scoring/ --stop-on-failure
```

Expected: all tests PASS (NormalizerTest, FactorCalculatorTest, ScoringEngineTest).

- [ ] **Run scoring engine on demo data**

```bash
docker compose exec app php artisan scoring:run --dry-run
```

Expected: "DRY RUN" message.

```bash
docker compose exec app php artisan scoring:run
```

Expected: "ModelRun #N completed — 22 securities ranked."

- [ ] **Inspect top rankings**

```bash
docker compose exec app php artisan tinker --execute="
\App\Models\SecurityRanking::with('security')
    ->whereHas('modelRun', fn(\$q) => \$q->where('status','completed')->orderByDesc('id')->limit(1))
    ->orderBy('rank')
    ->take(5)
    ->get()
    ->each(fn(\$r) => print(\$r->rank . '. ' . \$r->security->ticker . ' — ' . \$r->final_score . PHP_EOL));
"
```

Expected: 5 lines with rank, ticker, score.

- [ ] **Final commit**

```bash
git add -A
git commit -m "feat: complete scoring engine — FactorCalculator, Normalizer, ScoringEngine, scoring:run command"
```

---

## Self-Review Notes

- **Spec coverage:** all 7 spec sections covered (ScoringEngine, FactorCalculator, Normalizer, ExplanationBuilder, Command, Logging, Tests). ✓
- **Placeholder scan:** no TBD/TODO. All code is complete. ✓
- **Type consistency:** `FactorResult` properties match across all tasks. `ScoringEngine::execute()` uses `FactorResult::raw_value`, `::confidence`, `::components` consistently. ✓
- **Dry-run:** returns a non-persisted `ModelRun` stub — callers must not call `->id` on it. ScoringEngineTest verifies `ModelRun::count() === 0`. ✓
- **Risk penalty:** `config()` calls use second-arg defaults so tests without config bootstrapped still work. ✓
