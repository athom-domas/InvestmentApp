<?php

namespace Database\Factories;

use App\Models\Portfolio;
use App\Models\PortfolioPosition;
use App\Models\Security;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioPositionFactory extends Factory
{
    protected $model = PortfolioPosition::class;

    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'security_id' => Security::factory(),
            'quantity' => fake()->randomFloat(8, 1, 1000),
            'average_price' => fake()->randomFloat(6, 10, 1000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'opened_at' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'notes' => null,
        ];
    }
}
