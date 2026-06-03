<?php

namespace App\Console\Commands;

use App\Services\Scoring\ScoringEngine;
use Illuminate\Console\Command;

class ScoringRun extends Command
{
    protected $signature = 'scoring:run
        {--universe=ALL    : Exchange code filter (NASDAQ, NYSE, MIL) or ALL}
        {--model-version=  : Override config model version}
        {--limit=          : Score only the first N active securities}
        {--dry-run         : Compute without persisting to database}';

    protected $description = 'Run the scoring engine and compute security rankings';

    public function handle(ScoringEngine $engine): int
    {
        $options = [
            'universe'      => $this->option('universe'),
            'model_version' => $this->option('model-version') ?: null,
            'limit'         => $this->option('limit') ? (int) $this->option('limit') : null,
            'dry_run'       => (bool) $this->option('dry-run'),
        ];

        $this->info(sprintf(
            'Starting scoring run [universe=%s, version=%s%s]…',
            $options['universe'],
            $options['model_version'] ?? config('investment_scoring.model_version'),
            $options['dry_run'] ? ', DRY RUN' : ''
        ));

        $run = $engine->run($options);

        if ($options['dry_run']) {
            $this->info('[DRY RUN] Scoring computed — nothing persisted.');
        } else {
            $rankCount = $run->rankings()->count();
            $this->info("ModelRun #{$run->id} completed — {$rankCount} securities ranked.");
        }

        return self::SUCCESS;
    }
}
