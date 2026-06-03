# Scoring Engine Design — 2026-06-03

## Goal

Compute a ranked list of securities based on six explainable factors. The engine must be transparent (every score traceable to raw metrics), robust (handles missing data without crashing), and neutral (no buy/sell language).

---

## Architecture

```
ScoringEngine
  ├── creates ModelRun (status=running)
  ├── loads active Securities with Fundamentals + PriceBars
  ├── for each Security:
  │     └── FactorCalculator→FactorResult[6]
  ├── saves raw FactorValue records
  ├── for each factor:
  │     └── Normalizer.normalize(rawValues[], higherIsBetter) → score[]
  ├── for each Security:
  │     ├── final_score = weighted sum of normalized scores
  │     └── ExplanationBuilder→ summary + risks
  ├── saves SecurityRanking records
  ├── assigns rank (1 = best) ordered by final_score desc
  └── updates ModelRun (status=completed, finished_at)
```

---

## Components

### FactorCalculator

Receives: `Security` with eager-loaded `fundamentals` + `priceBars`.
Returns: array of 6 `FactorResult` DTOs.

**Sub-factor composition:**

| Factor | Sub-factors (raw) | Direction |
|---|---|---|
| quality | ROE, operating_margin, net_margin, FCF/revenue | higher=better |
| value | pe_ratio, ev_ebitda, price_to_sales, price_to_book | lower=better |
| growth | revenue_yoy, net_income_yoy, fcf_yoy (needs ≥2 periods) | higher=better |
| momentum | return_1m, return_3m, return_6m, return_12m | higher=better |
| financial_strength | 1−(D/E), cash/debt, 1−(liabilities/assets) | higher=better |
| risk | volatility, max_drawdown, data_quality_score (inverted: lower risk → higher score) | lower=better |

**Missing data policy:**
- Each sub-factor is optional. Confidence = (available sub-factors) / (total sub-factors).
- FactorResult.raw_value = weighted mean of available sub-factors (equal weight within factor).
- If confidence < 0.25 (fewer than 1-in-4 sub-factors available), raw_value = null.

**Value ratios (pre-computed in seeder):** pe_ratio, ev_ebitda, price_to_sales, price_to_book stored in `fundamentals`. Negative pe_ratio or ev_ebitda → treated as null (penalized by normalizer).

**Risk inversion:** volatility and max_drawdown are "lower is better". The FactorCalculator negates them before returning so the Normalizer always treats its input as "higher is better".

---

### Normalizer

Method: **percentile ranking** (more robust than z-score for small universes).

```
score_i = rank(value_i, all_non_null_values, ascending) / n × 100
```

Pipeline:
1. Separate nulls (they receive score 0).
2. Winsorize non-null values at 5th–95th percentile.
3. Rank winsorized values → map linearly to [0, 100].
4. If all values are identical → return 50 for all (no information).
5. If n < 3 → skip winsorization (not enough data for percentile clipping).

Sector normalization: used only when ≥3 securities share the same sector_id. Otherwise falls back to universe-wide normalization.

---

### ExplanationBuilder

Generates:
- `summary` (1–2 sentences): mentions top-2 strongest factors by normalized score.
- `risks` (1–3 bullet points): mentions weakest factors and any data gaps.
- Language: "presents characteristics worth examining", "flagged for review", "limited data available", "elevated volatility noted" — never "buy", "sell", "guaranteed", "best investment".

---

### ScoringEngine Config (`config/investment_scoring.php`)

```php
[
    'model_version' => '1.0.0',
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
    'liquidity_settings' => ['min_avg_volume' => 100_000],
    'score_scale'        => ['min' => 0, 'max' => 100],
]
```

---

### Artisan Command: `scoring:run`

Options:
- `--universe=ALL` — filter by exchange code (NASDAQ, NYSE, MIL) or ALL
- `--model-version=1.0.0` — override config
- `--limit=N` — score only first N securities (for debugging)
- `--dry-run` — compute everything, log output, skip database writes

---

### Seeder Updates

`DemoSecuritiesSeeder` updated to generate:
- 252 trading days of price bars per security (random walk from base price).
- Three fundamental periods: FY2022 (−30% from current), FY2023 (−15%), TTM2024 (current).
- Pre-computed ratios: pe_ratio, ev_ebitda, price_to_sales, price_to_book.

---

### DTOs

`FactorResult`:
```
string   $factor_code
?float   $raw_value       // null if confidence < threshold
float    $confidence      // 0.0–1.0
array    $components      // ['roe' => 0.24, 'operating_margin' => 0.31, ...]
```

`SecurityScoreResult`:
```
int      $security_id
float    $final_score
array    $factor_results  // FactorResult[]
array    $normalized      // ['quality' => 72.3, 'value' => 44.1, ...]
string   $summary
string   $risks
```

---

### Tests

| Test class | Coverage |
|---|---|
| `NormalizerTest` | percentile ranking, null handling, all-same-values, winsorization, n<3 |
| `FactorCalculatorTest` | momentum with known series, missing fundamentals, confidence decay |
| `ScoringEngineTest` | model_run created, final scores 0–100, ranks assigned, dry-run skips DB |

---

## Data Flow

```
securities (active)
    ↓ eager load fundamentals + price_bars
FactorCalculator → FactorResult[6] per security
    ↓ persist raw values
factor_values table
    ↓ Normalizer (per factor, across universe)
normalized scores [0–100]
    ↓ weighted sum + ExplanationBuilder
security_rankings table
    ↓ ORDER BY final_score DESC → assign rank
ModelRun updated (status=completed)
```

---

## Key Decisions

1. **Percentile ranking** over z-score: more robust with 20–500 securities; no normality assumption.
2. **Winsorize before ranking** (5th–95th): prevents extreme outliers from compressing the rest.
3. **Confidence weighting per factor**: missing sub-factors reduce confidence, which feeds the `risk` penalty — not the factor weight itself (weights stay as configured).
4. **Lower-is-better handled by negation** inside FactorCalculator before passing to Normalizer (Normalizer always processes "higher is better").
5. **Sector fallback**: if sector has < 3 peers, fall back to universe normalization silently.
6. **Single transaction per ModelRun**: all factor_values + security_rankings inserted atomically.
