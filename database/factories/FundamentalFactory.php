<?php

namespace Database\Factories;

use App\Models\Fundamental;
use App\Models\Security;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundamentalFactory extends Factory
{
    protected $model = Fundamental::class;

    public function definition(): array
    {
        $revenue = fake()->randomFloat(2, 1_000_000_000, 500_000_000_000);
        $grossMargin = fake()->randomFloat(4, 0.20, 0.75);
        $operatingMargin = fake()->randomFloat(4, 0.05, $grossMargin);
        $netMargin = fake()->randomFloat(4, 0.02, $operatingMargin);
        $grossProfit = round($revenue * $grossMargin, 2);
        $operatingIncome = round($revenue * $operatingMargin, 2);
        $netIncome = round($revenue * $netMargin, 2);
        $totalAssets = round($revenue * fake()->randomFloat(4, 0.5, 3.0), 2);
        $totalLiabilities = round($totalAssets * fake()->randomFloat(4, 0.3, 0.7), 2);
        $equity = $totalAssets - $totalLiabilities;
        $debt = round($totalLiabilities * fake()->randomFloat(4, 0.3, 0.8), 2);

        return [
            'security_id' => Security::factory(),
            'fiscal_period' => 'TTM',
            'fiscal_year' => 2024,
            'period_end_date' => fake()->dateTimeBetween('-12 months', '-1 month')->format('Y-m-d'),
            'revenue' => $revenue,
            'gross_profit' => $grossProfit,
            'operating_income' => $operatingIncome,
            'net_income' => $netIncome,
            'ebitda' => round($operatingIncome * 1.15, 2),
            'free_cash_flow' => round($netIncome * fake()->randomFloat(4, 0.7, 1.3), 2),
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_debt' => $debt,
            'cash_and_equivalents' => round($totalAssets * fake()->randomFloat(4, 0.05, 0.2), 2),
            'shareholders_equity' => $equity > 0 ? $equity : null,
            'shares_outstanding' => fake()->randomFloat(2, 100_000_000, 10_000_000_000),
            'gross_margin' => round($grossMargin, 6),
            'operating_margin' => round($operatingMargin, 6),
            'net_margin' => round($netMargin, 6),
            'return_on_equity' => $equity > 0 ? round($netIncome / $equity, 6) : null,
            'return_on_assets' => round($netIncome / $totalAssets, 6),
            'debt_to_equity' => $equity > 0 ? round($debt / $equity, 6) : null,
            'metadata' => null,
        ];
    }
}
