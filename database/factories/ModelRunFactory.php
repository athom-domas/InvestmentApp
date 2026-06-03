<?php

namespace Database\Factories;

use App\Models\ModelRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModelRunFactory extends Factory
{
    protected $model = ModelRun::class;

    public function definition(): array
    {
        $started = fake()->dateTimeBetween('-7 days', '-1 hour');

        return [
            'model_version' => '1.0.0',
            'universe' => fake()->randomElement(['SP500', 'FTSE_MIB', 'CUSTOM', 'DEMO']),
            'data_cutoff_at' => $started,
            'config_hash' => fake()->md5(),
            'status' => 'completed',
            'started_at' => $started,
            'finished_at' => fake()->dateTimeBetween($started, 'now'),
            'metadata' => null,
        ];
    }
}
