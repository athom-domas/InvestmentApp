<?php

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
