<?php

namespace App\Http\Controllers;

use App\Models\Exchange;
use App\Models\ModelRun;
use App\Models\Sector;
use App\Models\SecurityRanking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    private const SORTABLE = [
        'final_score', 'quality_score', 'value_score', 'growth_score',
        'momentum_score', 'financial_strength_score', 'risk_score',
    ];

    public function index(Request $request): Response
    {
        $modelRun = ModelRun::where('status', 'completed')->latest()->first();

        $rankings = collect();
        if ($modelRun) {
            $sort = in_array($request->input('sort'), self::SORTABLE, true)
                ? $request->input('sort')
                : 'final_score';

            $rankings = SecurityRanking::where('model_run_id', $modelRun->id)
                ->select(['id', 'security_id', 'final_score', 'rank', 'quality_score', 'value_score', 'growth_score', 'momentum_score', 'financial_strength_score', 'risk_score'])
                ->with([
                    'security:id,ticker,name,exchange_id,sector_id',
                    'security.exchange:id,code',
                    'security.sector:id,name',
                ])
                ->when($request->input('search'), function ($q, $search) {
                    $q->whereHas('security', fn($sq) =>
                        $sq->where('ticker', 'like', "%{$search}%")
                           ->orWhere('name', 'like', "%{$search}%")
                    );
                })
                ->when($request->input('sector_id'), function ($q, $sectorId) {
                    $q->whereHas('security', fn($sq) => $sq->where('sector_id', $sectorId));
                })
                ->when($request->input('exchange_id'), function ($q, $exchangeId) {
                    $q->whereHas('security', fn($sq) => $sq->where('exchange_id', $exchangeId));
                })
                ->when($request->input('min_score'), function ($q, $minScore) {
                    $q->where('final_score', '>=', $minScore);
                })
                ->orderByDesc($sort)
                ->paginate(50)
                ->withQueryString();
        }

        return Inertia::render('Rankings/Index', [
            'rankings'  => $rankings,
            'filters'   => $request->only(['search', 'sector_id', 'exchange_id', 'min_score', 'sort']),
            'sectors'   => Sector::orderBy('name')->get(['id', 'name']),
            'exchanges' => Exchange::orderBy('name')->get(['id', 'code', 'name']),
            'modelRun'  => $modelRun?->only(['id', 'model_version', 'finished_at']),
        ]);
    }
}
