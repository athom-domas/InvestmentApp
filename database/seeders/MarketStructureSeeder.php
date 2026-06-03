<?php

namespace Database\Seeders;

use App\Models\Exchange;
use App\Models\Industry;
use App\Models\Sector;
use Illuminate\Database\Seeder;

class MarketStructureSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedExchanges();
        $this->seedSectorsAndIndustries();
    }

    private function seedExchanges(): void
    {
        $exchanges = [
            ['code' => 'NASDAQ', 'name' => 'NASDAQ Stock Market',     'country' => 'US', 'currency' => 'USD', 'timezone' => 'America/New_York'],
            ['code' => 'NYSE',   'name' => 'New York Stock Exchange',  'country' => 'US', 'currency' => 'USD', 'timezone' => 'America/New_York'],
            ['code' => 'MIL',    'name' => 'Borsa Italiana',           'country' => 'IT', 'currency' => 'EUR', 'timezone' => 'Europe/Rome'],
        ];

        foreach ($exchanges as $exchange) {
            Exchange::firstOrCreate(['code' => $exchange['code']], $exchange);
        }
    }

    private function seedSectorsAndIndustries(): void
    {
        $structure = [
            'Technology' => [
                'Software', 'Semiconductors', 'Consumer Electronics',
                'IT Services', 'Networking', 'Cloud Computing',
            ],
            'Financials' => [
                'Banking', 'Insurance', 'Financial Services',
                'Asset Management', 'Investment Banking',
            ],
            'Healthcare' => [
                'Pharmaceuticals', 'Biotechnology', 'Health Insurance',
                'Medical Devices', 'Healthcare Services',
            ],
            'Consumer Defensive' => [
                'Beverages', 'Food Products', 'Household Products',
                'Personal Products', 'Tobacco',
            ],
            'Consumer Cyclical' => [
                'Automobiles', 'E-Commerce', 'Home Improvement',
                'Restaurants', 'Specialty Retail',
            ],
            'Industrials' => [
                'Aerospace & Defense', 'Machinery', 'Transportation',
                'Construction', 'Electrical Equipment',
            ],
            'Energy' => [
                'Oil & Gas', 'Oil Refining', 'Oil Services', 'Renewable Energy',
            ],
            'Utilities' => [
                'Electric Utilities', 'Gas Utilities', 'Water Utilities', 'Renewable Electric',
            ],
            'Communication Services' => [
                'Telecom Services', 'Entertainment', 'Social Media', 'Interactive Media',
            ],
            'Real Estate' => [
                'REITs', 'Real Estate Services', 'Commercial Real Estate',
            ],
            'Basic Materials' => [
                'Chemicals', 'Mining', 'Steel', 'Agriculture', 'Paper & Packaging',
            ],
        ];

        foreach ($structure as $sectorName => $industries) {
            $sector = Sector::firstOrCreate(['name' => $sectorName]);

            foreach ($industries as $industryName) {
                Industry::firstOrCreate(
                    ['sector_id' => $sector->id, 'name' => $industryName]
                );
            }
        }
    }
}
