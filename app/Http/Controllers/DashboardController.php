<?php

namespace App\Http\Controllers;

use App\Models\ModelRun;
use App\Models\Security;
use App\Models\SecurityRanking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $modelRun = ModelRun::where('status', 'completed')->latest()->first();

        $topRankings = [];
        if ($modelRun) {
            $topRankings = SecurityRanking::where('model_run_id', $modelRun->id)
                ->select(['id', 'security_id', 'final_score', 'rank', 'quality_score', 'value_score', 'growth_score', 'momentum_score', 'financial_strength_score', 'risk_score'])
                ->with([
                    'security:id,ticker,name,exchange_id,sector_id',
                    'security.exchange:id,code',
                    'security.sector:id,name',
                ])
                ->orderBy('rank')
                ->limit(20)
                ->get();
        }

        return Inertia::render('Dashboard', [
            'modelRun'       => $modelRun?->only(['id', 'model_version', 'universe', 'finished_at']),
            'topRankings'    => $topRankings,
            'securitiesCount' => Security::where('is_active', true)->count(),
            'watchlists'     => $request->user()->watchlists()->select('id', 'name')->orderBy('name')->limit(10)->get(),
        ]);
    }
}
