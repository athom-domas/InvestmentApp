<?php

namespace Database\Seeders;

use App\Models\FactorDefinition;
use Illuminate\Database\Seeder;

class FactorDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $factors = [
            ['code' => 'quality',            'name' => 'Quality',            'weight' => 0.25],
            ['code' => 'value',              'name' => 'Value',              'weight' => 0.20],
            ['code' => 'momentum',           'name' => 'Momentum',           'weight' => 0.20],
            ['code' => 'growth',             'name' => 'Growth',             'weight' => 0.15],
            ['code' => 'financial_strength', 'name' => 'Financial Strength', 'weight' => 0.10],
            ['code' => 'risk',               'name' => 'Risk',               'weight' => 0.10],
        ];

        foreach ($factors as $factor) {
            FactorDefinition::firstOrCreate(
                ['code' => $factor['code']],
                ['name' => $factor['name'], 'weight' => $factor['weight'], 'is_active' => true]
            );
        }
    }
}
