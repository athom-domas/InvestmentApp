<?php

namespace Database\Factories;

use App\Models\Industry;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndustryFactory extends Factory
{
    protected $model = Industry::class;

    public function definition(): array
    {
        return [
            'sector_id' => Sector::factory(),
            'name' => fake()->words(2, true),
        ];
    }
}
