<?php

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
                    $ret                          = ($latest - $past) / $past;
                    $components["return_{$label}"] = $ret;
                    $values[]                     = $ret;
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
                $vol      = sqrt($variance * 252);
                $components['volatility'] = $vol;
                $values[] = -$vol;
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
            $values[] = -$maxDd;
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
        $values[] = $dq - 1.0;

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
