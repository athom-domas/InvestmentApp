<?php

namespace Database\Factories;

use App\Models\Security;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityFactory extends Factory
{
    protected $model = Security::class;

    public function definition(): array
    {
        return [
            'exchange_id' => null,
            'sector_id' => null,
            'industry_id' => null,
            'ticker' => fake()->regexify('[A-Z]{1,5}'),
            'name' => fake()->company(),
            'isin' => null,
            'currency' => 'USD',
            'country' => 'US',
            'market_cap' => fake()->randomFloat(2, 1_000_000_000, 3_000_000_000_000),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
