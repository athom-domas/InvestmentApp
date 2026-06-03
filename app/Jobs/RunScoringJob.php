<?php

namespace App\Jobs;

use App\Services\Scoring\ScoringEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunScoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        private readonly string  $universe     = 'ALL',
        private readonly ?string $modelVersion = null,
    ) {}

    public function handle(ScoringEngine $engine): void
    {
        $version = $this->modelVersion ?? config('investment_scoring.model_version', '1.0.0');

        Log::info("RunScoringJob: starting [universe={$this->universe}, version={$version}]");

        $run = $engine->run([
            'universe'      => $this->universe,
            'model_version' => $this->modelVersion,
        ]);

        $rankCount = $run->rankings()->count();
        Log::info("RunScoringJob: completed ModelRun #{$run->id} — {$rankCount} securities ranked.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunScoringJob: failed — ' . $e->getMessage(), [
            'universe'      => $this->universe,
            'model_version' => $this->modelVersion,
        ]);
    }
}
