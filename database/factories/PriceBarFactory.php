<?php

namespace Database\Factories;

use App\Models\PriceBar;
use App\Models\Security;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceBarFactory extends Factory
{
    protected $model = PriceBar::class;

    public function definition(): array
    {
        $close = fake()->randomFloat(2, 10, 1000);
        $open = round($close * (1 + fake()->randomFloat(4, -0.03, 0.03)), 6);
        $high = round(max($close, $open) * (1 + fake()->randomFloat(4, 0, 0.02)), 6);
        $low = round(min($close, $open) * (1 - fake()->randomFloat(4, 0, 0.02)), 6);

        return [
            'security_id' => Security::factory(),
            'date' => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
            'adjusted_close' => $close,
            'volume' => fake()->numberBetween(100_000, 50_000_000),
        ];
    }
}
