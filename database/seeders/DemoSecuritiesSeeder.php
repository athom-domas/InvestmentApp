<?php

namespace Database\Seeders;

use App\Models\Exchange;
use App\Models\Fundamental;
use App\Models\Industry;
use App\Models\ModelRun;
use App\Models\PriceBar;
use App\Models\Sector;
use App\Models\Security;
use App\Models\SecurityRanking;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSecuritiesSeeder extends Seeder
{
    public function run(): void
    {
        $nasdaq = Exchange::where('code', 'NASDAQ')->firstOrFail();
        $nyse   = Exchange::where('code', 'NYSE')->firstOrFail();
        $mil    = Exchange::where('code', 'MIL')->firstOrFail();

        $sectors    = Sector::all()->keyBy('name');
        $industries = Industry::all()->keyBy('name');

        $securities = [
            ['ticker' => 'AAPL',  'name' => 'Apple Inc.',           'exchange' => $nasdaq, 'sector' => 'Technology',              'industry' => 'Consumer Electronics', 'currency' => 'USD', 'country' => 'US', 'market_cap' => 2_900_000_000_000, 'base_price' => 185.00, 'revenue' => 385_700_000_000, 'net_income' => 97_000_000_000,  'gross_margin' => 0.4530, 'net_margin' => 0.2515, 'total_assets' => 352_600_000_000, 'equity' => 62_100_000_000,  'debt' => 111_100_000_000, 'ebitda' => 123_000_000_000, 'fcf' => 99_000_000_000],
            ['ticker' => 'MSFT',  'name' => 'Microsoft Corp.',       'exchange' => $nasdaq, 'sector' => 'Technology',              'industry' => 'Software',             'currency' => 'USD', 'country' => 'US', 'market_cap' => 3_100_000_000_000, 'base_price' => 415.00, 'revenue' => 211_900_000_000, 'net_income' => 72_400_000_000,  'gross_margin' => 0.6993, 'net_margin' => 0.3416, 'total_assets' => 411_900_000_000, 'equity' => 206_200_000_000, 'debt' => 79_400_000_000,  'ebitda' => 100_000_000_000, 'fcf' => 74_000_000_000],
            ['ticker' => 'NVDA',  'name' => 'NVIDIA Corp.',          'exchange' => $nasdaq, 'sector' => 'Technology',              'industry' => 'Semiconductors',       'currency' => 'USD', 'country' => 'US', 'market_cap' => 2_200_000_000_000, 'base_price' => 875.00, 'revenue' => 60_900_000_000,  'net_income' => 29_800_000_000,  'gross_margin' => 0.7256, 'net_margin' => 0.4893, 'total_assets' => 65_700_000_000,  'equity' => 42_900_000_000,  'debt' => 8_500_000_000,   'ebitda' => 35_000_000_000,  'fcf' => 27_000_000_000],
            ['ticker' => 'CSCO',  'name' => 'Cisco Systems Inc.',    'exchange' => $nasdaq, 'sector' => 'Technology',              'industry' => 'Networking',           'currency' => 'USD', 'country' => 'US', 'market_cap' => 197_000_000_000,   'base_price' => 49.00,  'revenue' => 53_800_000_000,  'net_income' => 11_800_000_000,  'gross_margin' => 0.6320, 'net_margin' => 0.2193, 'total_assets' => 101_900_000_000, 'equity' => 39_700_000_000,  'debt' => 28_600_000_000,  'ebitda' => 16_500_000_000,  'fcf' => 13_000_000_000],
            ['ticker' => 'GOOGL', 'name' => 'Alphabet Inc.',         'exchange' => $nasdaq, 'sector' => 'Communication Services', 'industry' => 'Interactive Media',    'currency' => 'USD', 'country' => 'US', 'market_cap' => 2_100_000_000_000, 'base_price' => 170.00, 'revenue' => 307_400_000_000, 'net_income' => 73_800_000_000,  'gross_margin' => 0.5679, 'net_margin' => 0.2401, 'total_assets' => 402_400_000_000, 'equity' => 292_000_000_000, 'debt' => 13_200_000_000,  'ebitda' => 95_000_000_000,  'fcf' => 69_000_000_000],
            ['ticker' => 'META',  'name' => 'Meta Platforms Inc.',   'exchange' => $nasdaq, 'sector' => 'Communication Services', 'industry' => 'Social Media',         'currency' => 'USD', 'country' => 'US', 'market_cap' => 1_300_000_000_000, 'base_price' => 505.00, 'revenue' => 134_900_000_000, 'net_income' => 39_100_000_000,  'gross_margin' => 0.8099, 'net_margin' => 0.2899, 'total_assets' => 229_600_000_000, 'equity' => 153_200_000_000, 'debt' => 18_400_000_000,  'ebitda' => 58_000_000_000,  'fcf' => 43_000_000_000],
            ['ticker' => 'AMZN',  'name' => 'Amazon.com Inc.',       'exchange' => $nasdaq, 'sector' => 'Consumer Cyclical',      'industry' => 'E-Commerce',           'currency' => 'USD', 'country' => 'US', 'market_cap' => 1_900_000_000_000, 'base_price' => 185.00, 'revenue' => 574_800_000_000, 'net_income' => 30_400_000_000,  'gross_margin' => 0.4700, 'net_margin' => 0.0529, 'total_assets' => 527_900_000_000, 'equity' => 201_900_000_000, 'debt' => 140_100_000_000, 'ebitda' => 85_000_000_000,  'fcf' => 36_000_000_000],
            ['ticker' => 'JPM',   'name' => 'JPMorgan Chase & Co.',  'exchange' => $nyse,   'sector' => 'Financials',             'industry' => 'Banking',              'currency' => 'USD', 'country' => 'US', 'market_cap' => 580_000_000_000,   'base_price' => 200.00, 'revenue' => 162_400_000_000, 'net_income' => 49_600_000_000,  'gross_margin' => 0.6100, 'net_margin' => 0.3054, 'total_assets' => 3_875_000_000_000,'equity' => 329_000_000_000, 'debt' => 512_000_000_000, 'ebitda' => 68_000_000_000,  'fcf' => 45_000_000_000],
            ['ticker' => 'V',     'name' => 'Visa Inc.',             'exchange' => $nyse,   'sector' => 'Financials',             'industry' => 'Financial Services',   'currency' => 'USD', 'country' => 'US', 'market_cap' => 556_000_000_000,   'base_price' => 275.00, 'revenue' => 33_100_000_000,  'net_income' => 17_300_000_000,  'gross_margin' => 0.7970, 'net_margin' => 0.5227, 'total_assets' => 91_800_000_000,  'equity' => 38_800_000_000,  'debt' => 16_500_000_000,  'ebitda' => 25_000_000_000,  'fcf' => 19_000_000_000],
            ['ticker' => 'MA',    'name' => 'Mastercard Inc.',       'exchange' => $nyse,   'sector' => 'Financials',             'industry' => 'Financial Services',   'currency' => 'USD', 'country' => 'US', 'market_cap' => 436_000_000_000,   'base_price' => 470.00, 'revenue' => 25_100_000_000,  'net_income' => 11_200_000_000,  'gross_margin' => 0.7620, 'net_margin' => 0.4462, 'total_assets' => 41_900_000_000,  'equity' => 8_100_000_000,   'debt' => 15_600_000_000,  'ebitda' => 15_000_000_000,  'fcf' => 11_500_000_000],
            ['ticker' => 'JNJ',   'name' => 'Johnson & Johnson',     'exchange' => $nyse,   'sector' => 'Healthcare',             'industry' => 'Pharmaceuticals',      'currency' => 'USD', 'country' => 'US', 'market_cap' => 373_000_000_000,   'base_price' => 155.00, 'revenue' => 85_200_000_000,  'net_income' => 13_800_000_000,  'gross_margin' => 0.6872, 'net_margin' => 0.1620, 'total_assets' => 167_600_000_000, 'equity' => 68_800_000_000,  'debt' => 35_400_000_000,  'ebitda' => 22_000_000_000,  'fcf' => 14_500_000_000],
            ['ticker' => 'UNH',   'name' => 'UnitedHealth Group',    'exchange' => $nyse,   'sector' => 'Healthcare',             'industry' => 'Health Insurance',     'currency' => 'USD', 'country' => 'US', 'market_cap' => 482_000_000_000,   'base_price' => 510.00, 'revenue' => 366_000_000_000, 'net_income' => 22_400_000_000,  'gross_margin' => 0.2400, 'net_margin' => 0.0612, 'total_assets' => 273_700_000_000, 'equity' => 93_400_000_000,  'debt' => 55_700_000_000,  'ebitda' => 30_000_000_000,  'fcf' => 22_000_000_000],
            ['ticker' => 'PFE',   'name' => 'Pfizer Inc.',           'exchange' => $nyse,   'sector' => 'Healthcare',             'industry' => 'Pharmaceuticals',      'currency' => 'USD', 'country' => 'US', 'market_cap' => 158_000_000_000,   'base_price' => 28.00,  'revenue' => 58_500_000_000,  'net_income' => 2_100_000_000,   'gross_margin' => 0.6630, 'net_margin' => 0.0359, 'total_assets' => 226_500_000_000, 'equity' => 96_900_000_000,  'debt' => 62_000_000_000,  'ebitda' => 8_000_000_000,   'fcf' => 5_000_000_000],
            ['ticker' => 'PG',    'name' => 'Procter & Gamble Co.',  'exchange' => $nyse,   'sector' => 'Consumer Defensive',    'industry' => 'Household Products',   'currency' => 'USD', 'country' => 'US', 'market_cap' => 378_000_000_000,   'base_price' => 160.00, 'revenue' => 84_000_000_000,  'net_income' => 14_800_000_000,  'gross_margin' => 0.5000, 'net_margin' => 0.1762, 'total_assets' => 120_200_000_000, 'equity' => 47_100_000_000,  'debt' => 35_700_000_000,  'ebitda' => 20_000_000_000,  'fcf' => 14_000_000_000],
            ['ticker' => 'KO',    'name' => 'The Coca-Cola Co.',     'exchange' => $nyse,   'sector' => 'Consumer Defensive',    'industry' => 'Beverages',            'currency' => 'USD', 'country' => 'US', 'market_cap' => 267_000_000_000,   'base_price' => 62.00,  'revenue' => 45_800_000_000,  'net_income' => 10_700_000_000,  'gross_margin' => 0.5980, 'net_margin' => 0.2358, 'total_assets' => 97_700_000_000,  'equity' => 26_400_000_000,  'debt' => 35_500_000_000,  'ebitda' => 14_000_000_000,  'fcf' => 10_500_000_000],
            ['ticker' => 'HD',    'name' => 'The Home Depot Inc.',   'exchange' => $nyse,   'sector' => 'Consumer Cyclical',     'industry' => 'Home Improvement',     'currency' => 'USD', 'country' => 'US', 'market_cap' => 337_000_000_000,   'base_price' => 340.00, 'revenue' => 153_000_000_000, 'net_income' => 15_100_000_000,  'gross_margin' => 0.3340, 'net_margin' => 0.0987, 'total_assets' => 76_500_000_000,  'equity' => -1_700_000_000,  'debt' => 42_700_000_000,  'ebitda' => 22_000_000_000,  'fcf' => 15_000_000_000],
            ['ticker' => 'DIS',   'name' => 'The Walt Disney Co.',   'exchange' => $nyse,   'sector' => 'Communication Services','industry' => 'Entertainment',        'currency' => 'USD', 'country' => 'US', 'market_cap' => 206_000_000_000,   'base_price' => 113.00, 'revenue' => 88_900_000_000,  'net_income' => 2_400_000_000,   'gross_margin' => 0.3600, 'net_margin' => 0.0270, 'total_assets' => 202_600_000_000, 'equity' => 101_300_000_000, 'debt' => 47_100_000_000,  'ebitda' => 12_000_000_000,  'fcf' => 5_000_000_000],
            ['ticker' => 'XOM',   'name' => 'Exxon Mobil Corp.',     'exchange' => $nyse,   'sector' => 'Energy',                'industry' => 'Oil & Gas',            'currency' => 'USD', 'country' => 'US', 'market_cap' => 432_000_000_000,   'base_price' => 108.00, 'revenue' => 398_700_000_000, 'net_income' => 36_000_000_000,  'gross_margin' => 0.3200, 'net_margin' => 0.0903, 'total_assets' => 376_300_000_000, 'equity' => 168_600_000_000, 'debt' => 40_600_000_000,  'ebitda' => 55_000_000_000,  'fcf' => 35_000_000_000],
            ['ticker' => 'ENI',   'name' => 'Eni S.p.A.',            'exchange' => $mil,    'sector' => 'Energy',                'industry' => 'Oil & Gas',            'currency' => 'EUR', 'country' => 'IT', 'market_cap' => 51_000_000_000,    'base_price' => 14.50,  'revenue' => 94_000_000_000,  'net_income' => 5_800_000_000,   'gross_margin' => 0.2800, 'net_margin' => 0.0617, 'total_assets' => 121_000_000_000, 'equity' => 53_600_000_000,  'debt' => 18_200_000_000,  'ebitda' => 14_000_000_000,  'fcf' => 8_000_000_000],
            ['ticker' => 'ENEL',  'name' => 'Enel S.p.A.',           'exchange' => $mil,    'sector' => 'Utilities',             'industry' => 'Electric Utilities',   'currency' => 'EUR', 'country' => 'IT', 'market_cap' => 63_000_000_000,    'base_price' => 6.20,   'revenue' => 92_900_000_000,  'net_income' => 1_800_000_000,   'gross_margin' => 0.2100, 'net_margin' => 0.0194, 'total_assets' => 185_900_000_000, 'equity' => 56_400_000_000,  'debt' => 63_200_000_000,  'ebitda' => 10_000_000_000,  'fcf' => 4_000_000_000],
            ['ticker' => 'UCG',   'name' => 'UniCredit S.p.A.',      'exchange' => $mil,    'sector' => 'Financials',            'industry' => 'Banking',              'currency' => 'EUR', 'country' => 'IT', 'market_cap' => 65_000_000_000,    'base_price' => 35.50,  'revenue' => 22_700_000_000,  'net_income' => 8_600_000_000,   'gross_margin' => 0.7200, 'net_margin' => 0.3788, 'total_assets' => 728_000_000_000, 'equity' => 69_700_000_000,  'debt' => 85_000_000_000,  'ebitda' => 12_000_000_000,  'fcf' => 9_000_000_000],
            ['ticker' => 'STLAM', 'name' => 'Stellantis N.V.',       'exchange' => $mil,    'sector' => 'Consumer Cyclical',     'industry' => 'Automobiles',          'currency' => 'EUR', 'country' => 'IT', 'market_cap' => 53_000_000_000,    'base_price' => 17.00,  'revenue' => 189_500_000_000, 'net_income' => 18_600_000_000,  'gross_margin' => 0.1950, 'net_margin' => 0.0981, 'total_assets' => 127_000_000_000, 'equity' => 55_200_000_000,  'debt' => 15_800_000_000,  'ebitda' => 25_000_000_000,  'fcf' => 16_000_000_000],
        ];

        $created = [];
        foreach ($securities as $data) {
            $sector   = $sectors->get($data['sector']);
            $industry = $industries->get($data['industry']);

            $security = Security::firstOrCreate(
                ['ticker' => $data['ticker'], 'exchange_id' => $data['exchange']->id],
                [
                    'name'        => $data['name'],
                    'exchange_id' => $data['exchange']->id,
                    'sector_id'   => $sector?->id,
                    'industry_id' => $industry?->id,
                    'currency'    => $data['currency'],
                    'country'     => $data['country'],
                    'market_cap'  => $data['market_cap'],
                    'is_active'   => true,
                    'metadata'    => ['demo' => true],
                ]
            );

            $this->seedPriceBars($security, $data['base_price']);
            $this->seedFundamentals($security, $data);

            $created[] = $security;
        }

        $this->seedDemoModelRun($created);
    }

    private function seedPriceBars(Security $security, float $basePrice): void
    {
        $tradingDays = $this->lastTradingDays(252);
        $price       = $basePrice;
        $rows        = [];
        $now         = now();

        foreach ($tradingDays as $date) {
            $change = rand(-300, 300) / 10000;
            $close  = round($price * (1 + $change), 6);
            $open   = round($price * (1 + rand(-100, 100) / 10000), 6);
            $high   = round(max($close, $open) * (1 + rand(0, 100) / 10000), 6);
            $low    = round(min($close, $open) * (1 - rand(0, 100) / 10000), 6);

            $rows[] = [
                'security_id'    => $security->id,
                'date'           => $date->toDateString(),
                'open'           => $open,
                'high'           => $high,
                'low'            => $low,
                'close'          => $close,
                'adjusted_close' => $close,
                'volume'         => rand(500_000, 80_000_000),
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            $price = $close;
        }

        DB::table('price_bars')->insertOrIgnore($rows);
    }

    private function seedFundamentals(Security $security, array $data): void
    {
        $periods = [
            ['period' => 'FY',  'year' => 2022, 'multiplier' => 0.70, 'end' => '2022-12-31'],
            ['period' => 'FY',  'year' => 2023, 'multiplier' => 0.85, 'end' => '2023-12-31'],
            ['period' => 'TTM', 'year' => 2024, 'multiplier' => 1.00, 'end' => '2024-12-31'],
        ];

        foreach ($periods as $p) {
            $m           = $p['multiplier'];
            $revenue     = round($data['revenue'] * $m, 2);
            $netIncome   = round($data['net_income'] * $m, 2);
            $ebitda      = round($data['ebitda'] * $m, 2);
            $fcf         = round($data['fcf'] * $m, 2);
            $equity      = $data['equity'] > 0 ? round($data['equity'] * $m, 2) : $data['equity'];
            $debt        = round($data['debt'] * $m, 2);
            $assets      = round($data['total_assets'] * $m, 2);
            $liabilities = round($assets - $equity, 2);
            $cash        = round($assets * 0.10, 2);
            $shares      = $data['market_cap'] / $data['base_price'];

            $mktCap   = $data['market_cap'];
            $ev       = $mktCap + $debt - $cash;
            $peRatio  = $netIncome > 0 ? round($mktCap / $netIncome, 6) : null;
            $evEbitda = $ebitda > 0   ? round($ev / $ebitda, 6) : null;
            $pts      = $revenue > 0  ? round($mktCap / $revenue, 6) : null;
            $ptb      = $equity > 0   ? round($mktCap / $equity, 6) : null;
            $epsVal   = $shares > 0   ? round($netIncome / $shares, 6) : null;

            Fundamental::firstOrCreate(
                [
                    'security_id'     => $security->id,
                    'fiscal_period'   => $p['period'],
                    'fiscal_year'     => $p['year'],
                    'period_end_date' => $p['end'],
                ],
                [
                    'revenue'              => $revenue,
                    'gross_profit'         => round($revenue * $data['gross_margin'], 2),
                    'operating_income'     => round($revenue * $data['gross_margin'] * 0.70, 2),
                    'net_income'           => $netIncome,
                    'ebitda'               => $ebitda,
                    'free_cash_flow'       => $fcf,
                    'total_assets'         => $assets,
                    'total_liabilities'    => max(0, $liabilities),
                    'total_debt'           => $debt,
                    'cash_and_equivalents' => $cash,
                    'shareholders_equity'  => $equity > 0 ? $equity : null,
                    'shares_outstanding'   => round($shares, 2),
                    'eps'                  => $epsVal,
                    'pe_ratio'             => $peRatio,
                    'ev_ebitda'            => $evEbitda,
                    'price_to_sales'       => $pts,
                    'price_to_book'        => $ptb,
                    'gross_margin'         => $data['gross_margin'],
                    'operating_margin'     => round($data['gross_margin'] * 0.70, 6),
                    'net_margin'           => $data['net_margin'],
                    'return_on_equity'     => $equity > 0 ? round($netIncome / $equity, 6) : null,
                    'return_on_assets'     => round($netIncome / $assets, 6),
                    'debt_to_equity'       => $equity > 0 ? round($debt / $equity, 6) : null,
                    'metadata'             => ['demo' => true],
                ]
            );
        }
    }

    private function seedDemoModelRun(array $securities): void
    {
        $run = ModelRun::create([
            'model_version'  => '1.0.0',
            'universe'       => 'DEMO',
            'data_cutoff_at' => now(),
            'config_hash'    => md5('demo-v1'),
            'status'         => 'completed',
            'started_at'     => now()->subMinutes(2),
            'finished_at'    => now(),
            'metadata'       => ['demo' => true],
        ]);

        $rows = [];
        foreach ($securities as $security) {
            $q = round(rand(3000, 9500) / 100, 4);
            $v = round(rand(3000, 9500) / 100, 4);
            $g = round(rand(3000, 9500) / 100, 4);
            $m = round(rand(3000, 9500) / 100, 4);
            $f = round(rand(3000, 9500) / 100, 4);
            $r = round(rand(3000, 9500) / 100, 4);

            $rows[] = [
                'model_run_id'             => $run->id,
                'security_id'              => $security->id,
                'final_score'              => round($q * 0.25 + $v * 0.20 + $m * 0.20 + $g * 0.15 + $f * 0.10 + $r * 0.10, 4),
                'rank'                     => null,
                'quality_score'            => $q,
                'value_score'              => $v,
                'growth_score'             => $g,
                'momentum_score'           => $m,
                'financial_strength_score' => $f,
                'risk_score'               => $r,
                'metadata'                 => json_encode(['demo' => true]),
                'created_at'               => now(),
                'updated_at'               => now(),
            ];
        }

        DB::table('security_rankings')->insert($rows);

        $rank = 1;
        SecurityRanking::where('model_run_id', $run->id)
            ->orderByDesc('final_score')
            ->each(fn ($r) => $r->update(['rank' => $rank++]));
    }

    private function lastTradingDays(int $count): array
    {
        $days = [];
        $date = Carbon::today()->subDay();
        while (count($days) < $count) {
            if (! $date->isWeekend()) {
                $days[] = $date->copy();
            }
            $date->subDay();
        }
        return array_reverse($days);
    }
}
