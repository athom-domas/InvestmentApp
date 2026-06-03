<?php

namespace Tests\Feature\Scoring;

use App\Models\Fundamental;
use App\Models\ModelRun;
use App\Models\PriceBar;
use App\Models\Security;
use App\Models\SecurityRanking;
use App\Services\Scoring\ScoringEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringEngineTest extends TestCase
{
    use RefreshDatabase;

    private ScoringEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(ScoringEngine::class);
    }

    public function test_model_run_created_with_completed_status(): void
    {
        $this->buildUniverse(3);
        $run = $this->engine->run();

        $this->assertDatabaseHas('model_runs', ['id' => $run->id, 'status' => 'completed']);
        $this->assertNotNull($run->fresh()->finished_at);
    }

    public function test_rankings_created_for_all_active_securities(): void
    {
        $this->buildUniverse(4);
        $run = $this->engine->run();

        $this->assertSame(4, SecurityRanking::where('model_run_id', $run->id)->count());
    }

    public function test_inactive_securities_excluded(): void
    {
        $this->buildUniverse(3);
        Security::factory()->create(['is_active' => false]);

        $run = $this->engine->run();

        $this->assertSame(3, SecurityRanking::where('model_run_id', $run->id)->count());
    }

    public function test_all_final_scores_between_0_and_100(): void
    {
        $this->buildUniverse(5);
        $run = $this->engine->run();

        SecurityRanking::where('model_run_id', $run->id)->each(function ($r) {
            $this->assertGreaterThanOrEqual(0.0, (float) $r->final_score);
            $this->assertLessThanOrEqual(100.0, (float) $r->final_score);
        });
    }

    public function test_ranks_assigned_sequentially_from_1(): void
    {
        $this->buildUniverse(5);
        $run = $this->engine->run();

        $ranks = SecurityRanking::where('model_run_id', $run->id)
            ->orderBy('rank')
            ->pluck('rank')
            ->toArray();

        $this->assertSame(range(1, 5), $ranks);
    }

    public function test_rank_1_has_highest_final_score(): void
    {
        $this->buildUniverse(5);
        $run = $this->engine->run();

        $first  = SecurityRanking::where('model_run_id', $run->id)->orderBy('rank')->first();
        $others = SecurityRanking::where('model_run_id', $run->id)->where('rank', '>', 1)->get();

        foreach ($others as $other) {
            $this->assertGreaterThanOrEqual((float) $other->final_score, (float) $first->final_score);
        }
    }

    public function test_dry_run_does_not_persist_anything(): void
    {
        $this->buildUniverse(3);
        $this->engine->run(['dry_run' => true]);

        $this->assertSame(0, ModelRun::count());
        $this->assertSame(0, SecurityRanking::count());
    }

    public function test_limit_option_restricts_scored_securities(): void
    {
        $this->buildUniverse(5);
        $run = $this->engine->run(['limit' => 2]);

        $this->assertSame(2, SecurityRanking::where('model_run_id', $run->id)->count());
    }

    private function buildUniverse(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $s = Security::factory()->create(['is_active' => true]);

            Fundamental::factory()->create([
                'security_id'     => $s->id,
                'fiscal_year'     => 2023,
                'fiscal_period'   => 'FY',
                'period_end_date' => '2023-12-31',
            ]);
            Fundamental::factory()->create([
                'security_id'     => $s->id,
                'fiscal_year'     => 2024,
                'fiscal_period'   => 'TTM',
                'period_end_date' => '2024-12-31',
            ]);

            for ($j = 0; $j < 30; $j++) {
                PriceBar::factory()->create([
                    'security_id' => $s->id,
                    'date'        => now()->subDays(31 - $j)->toDateString(),
                    'close'       => 100 + rand(-5, 5),
                    'open' => 100.0, 'high' => 105.0, 'low' => 95.0,
                ]);
            }
        }
    }
}
