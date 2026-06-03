<?php

namespace Database\Factories;

use App\Models\ModelRun;
use App\Models\Security;
use App\Models\SecurityRanking;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityRankingFactory extends Factory
{
    protected $model = SecurityRanking::class;

    public function definition(): array
    {
        $quality = fake()->randomFloat(4, 0, 100);
        $value = fake()->randomFloat(4, 0, 100);
        $growth = fake()->randomFloat(4, 0, 100);
        $momentum = fake()->randomFloat(4, 0, 100);
        $financialStrength = fake()->randomFloat(4, 0, 100);
        $risk = fake()->randomFloat(4, 0, 100);

        $finalScore = round(
            $quality * 0.25 + $value * 0.20 + $momentum * 0.20
            + $growth * 0.15 + $financialStrength * 0.10 + $risk * 0.10,
            4
        );

        return [
            'model_run_id' => ModelRun::factory(),
            'security_id' => Security::factory(),
            'final_score' => $finalScore,
            'rank' => null,
            'quality_score' => $quality,
            'value_score' => $value,
            'growth_score' => $growth,
            'momentum_score' => $momentum,
            'financial_strength_score' => $financialStrength,
            'risk_score' => $risk,
            'metadata' => null,
        ];
    }
}
