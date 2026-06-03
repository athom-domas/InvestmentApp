<?php

namespace App\Services\Scoring;

use App\Models\ModelRun;
use App\Models\Security;
use App\Services\Scoring\DTO\SecurityScoreResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScoringEngine
{
    private const FACTOR_CODES = [
        'quality', 'value', 'growth', 'momentum', 'financial_strength', 'risk',
    ];

    public function __construct(
        private readonly FactorCalculator   $factorCalculator,
        private readonly Normalizer         $normalizer,
        private readonly ExplanationBuilder $explanationBuilder,
    ) {}

    /**
     * @param  array{
     *     universe?: string,
     *     model_version?: string|null,
     *     limit?: int|null,
     *     dry_run?: bool,
     * }  $options
     */
    public function run(array $options = []): ModelRun
    {
        $universe     = $options['universe']      ?? config('investment_scoring.default_universe', 'ALL');
        $modelVersion = $options['model_version'] ?? config('investment_scoring.model_version', '1.0.0');
        $limit        = isset($options['limit']) ? (int) $options['limit'] : null;
        $dryRun       = (bool) ($options['dry_run'] ?? false);

        if ($dryRun) {
            return $this->computeOnly($universe, $modelVersion, $limit);
        }

        $modelRun = ModelRun::create([
            'model_version'  => $modelVersion,
            'universe'       => $universe,
            'data_cutoff_at' => now(),
            'config_hash'    => md5(json_encode(config('investment_scoring'))),
            'status'         => 'running',
            'started_at'     => now(),
        ]);

        try {
            $this->execute($modelRun, $universe, $limit);
            $modelRun->update(['status' => 'completed', 'finished_at' => now()]);
        } catch (\Throwable $e) {
            $modelRun->update(['status' => 'failed', 'finished_at' => now()]);
            Log::error("Scoring run #{$modelRun->id} failed: " . $e->getMessage());
            throw $e;
        }

        return $modelRun->fresh();
    }

    private function execute(ModelRun $modelRun, string $universe, ?int $limit): void
    {
        $securities = $this->loadSecurities($universe, $limit);

        if ($securities->isEmpty()) {
            Log::warning("Scoring run #{$modelRun->id}: no active securities found.");
            return;
        }

        $allResults = [];
        foreach ($securities as $security) {
            try {
                $allResults[$security->id] = $this->factorCalculator->calculate($security);
            } catch (\Throwable $e) {
                Log::warning("Scoring: skipping security #{$security->id} ({$security->ticker}): {$e->getMessage()}");
            }
        }

        $weights       = config('investment_scoring.factor_weights');
        $normalizedAll = [];

        foreach (self::FACTOR_CODES as $code) {
            $rawValues = [];
            foreach ($allResults as $sid => $results) {
                $rawValues[$sid] = $results[$code]->raw_value ?? null;
            }
            $normalizedAll[$code] = $this->normalizer->normalize($rawValues);
        }

        $scoreResults = [];
        foreach ($allResults as $sid => $factorResults) {
            $normalized = [];
            foreach (self::FACTOR_CODES as $code) {
                $normalized[$code] = $normalizedAll[$code][$sid] ?? 0.0;
            }

            $finalScore = 0.0;
            foreach ($weights as $code => $weight) {
                $finalScore += ($normalized[$code] ?? 0.0) * $weight;
            }

            $security    = $securities->firstWhere('id', $sid);
            $explanation = $this->explanationBuilder->build($security, $normalized, $factorResults);

            $scoreResults[$sid] = new SecurityScoreResult(
                security_id:    $sid,
                final_score:    round($finalScore, 4),
                factor_results: $factorResults,
                normalized:     $normalized,
                summary:        $explanation['summary'],
                risks:          $explanation['risks'],
            );
        }

        DB::transaction(function () use ($modelRun, $scoreResults, $normalizedAll) {
            $now = now();

            $fvRows = [];
            foreach ($scoreResults as $sid => $sr) {
                foreach ($sr->factor_results as $code => $fr) {
                    $fvRows[] = [
                        'model_run_id'     => $modelRun->id,
                        'security_id'      => $sid,
                        'factor_code'      => $code,
                        'raw_value'        => $fr->raw_value,
                        'normalized_value' => $normalizedAll[$code][$sid] ?? null,
                        'score'            => $normalizedAll[$code][$sid] ?? 0.0,
                        'explanation'      => json_encode($fr->components),
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }
            }
            DB::table('factor_values')->insert($fvRows);

            $sorted = collect($scoreResults)->sortByDesc('final_score')->values();
            $rank   = 1;
            $srRows = [];
            foreach ($sorted as $sr) {
                $n        = $sr->normalized;
                $srRows[] = [
                    'model_run_id'             => $modelRun->id,
                    'security_id'              => $sr->security_id,
                    'final_score'              => $sr->final_score,
                    'rank'                     => $rank++,
                    'quality_score'            => $n['quality']            ?? null,
                    'value_score'              => $n['value']              ?? null,
                    'growth_score'             => $n['growth']             ?? null,
                    'momentum_score'           => $n['momentum']           ?? null,
                    'financial_strength_score' => $n['financial_strength'] ?? null,
                    'risk_score'               => $n['risk']               ?? null,
                    'summary'                  => $sr->summary,
                    'risks'                    => $sr->risks,
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ];
            }
            DB::table('security_rankings')->insert($srRows);
        });
    }

    private function computeOnly(string $universe, string $modelVersion, ?int $limit): ModelRun
    {
        $securities = $this->loadSecurities($universe, $limit);
        Log::info("[DRY RUN] Would score {$securities->count()} securities (universe: {$universe}, v{$modelVersion}).");
        foreach ($securities as $security) {
            Log::info("[DRY RUN] {$security->ticker}");
        }
        return new ModelRun([
            'model_version' => $modelVersion,
            'universe'      => $universe,
            'status'        => 'dry_run',
        ]);
    }

    private function loadSecurities(string $universe, ?int $limit)
    {
        $query = Security::where('is_active', true)
            ->with([
                'fundamentals' => fn ($q) => $q->orderByDesc('fiscal_year'),
                'priceBars'    => fn ($q) => $q->orderBy('date'),
            ]);

        if ($universe !== 'ALL') {
            $query->whereHas('exchange', fn ($q) => $q->where('code', $universe));
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
