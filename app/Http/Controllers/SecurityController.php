<?php

namespace App\Http\Controllers;

use App\Models\FactorValue;
use App\Models\Fundamental;
use App\Models\ModelRun;
use App\Models\PriceBar;
use App\Models\Security;
use App\Models\SecurityRanking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function show(Request $request, Security $security): Response
    {
        $security->load([
            'exchange:id,code,name,currency',
            'sector:id,name',
            'industry:id,name',
        ]);

        $modelRun = ModelRun::where('status', 'completed')->latest()->first();

        $ranking = null;
        $factorValues = collect();
        if ($modelRun) {
            $ranking = SecurityRanking::where('model_run_id', $modelRun->id)
                ->where('security_id', $security->id)
                ->first();

            $factorValues = FactorValue::where('model_run_id', $modelRun->id)
                ->where('security_id', $security->id)
                ->get(['factor_code', 'raw_value', 'normalized_value', 'score'])
                ->keyBy('factor_code');
        }

        $fundamentals = Fundamental::where('security_id', $security->id)
            ->orderByDesc('period_end_date')
            ->limit(3)
            ->get();

        $priceBars = PriceBar::where('security_id', $security->id)
            ->select(['date', 'open', 'high', 'low', 'close', 'adjusted_close', 'volume'])
            ->orderByDesc('date')
            ->limit(60)
            ->get()
            ->reverse()
            ->values();

        $userWatchlists = $request->user()
            ->watchlists()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $inWatchlistIds = $request->user()
            ->watchlists()
            ->whereHas('securities', fn($q) => $q->where('securities.id', $security->id))
            ->pluck('watchlists.id')
            ->toArray();

        return Inertia::render('Securities/Show', [
            'security'       => $security,
            'modelRun'       => $modelRun?->only(['id', 'model_version', 'finished_at']),
            'ranking'        => $ranking,
            'factorValues'   => $factorValues,
            'fundamentals'   => $fundamentals,
            'priceBars'      => $priceBars,
            'watchlists'     => $userWatchlists,
            'inWatchlistIds' => $inWatchlistIds,
        ]);
    }
}
