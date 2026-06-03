<?php

namespace Database\Factories;

use App\Models\Exchange;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeFactory extends Factory
{
    protected $model = Exchange::class;

    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Z]{4}'),
            'name' => fake()->company() . ' Exchange',
            'country' => fake()->country(),
            'currency' => fake()->currencyCode(),
            'timezone' => fake()->timezone(),
        ];
    }
}
