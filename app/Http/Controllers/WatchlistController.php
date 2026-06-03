<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWatchlistItemRequest;
use App\Http\Requests\StoreWatchlistRequest;
use App\Models\ModelRun;
use App\Models\Security;
use App\Models\SecurityRanking;
use App\Models\Watchlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WatchlistController extends Controller
{
    public function index(Request $request): Response
    {
        $watchlists = $request->user()
            ->watchlists()
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return Inertia::render('Watchlists/Index', [
            'watchlists' => $watchlists,
        ]);
    }

    public function store(StoreWatchlistRequest $request): RedirectResponse
    {
        $watchlist = $request->user()->watchlists()->create($request->validated());

        return redirect()->route('watchlists.show', $watchlist);
    }

    public function show(Watchlist $watchlist): Response
    {
        $this->authorize('view', $watchlist);

        $watchlist->load([
            'securities:id,ticker,name,exchange_id,sector_id,market_cap',
            'securities.exchange:id,code',
            'securities.sector:id,name',
        ]);

        $latestRunId = ModelRun::where('status', 'completed')->latest()->value('id');
        $rankings = collect();
        if ($latestRunId && $watchlist->securities->isNotEmpty()) {
            $rankings = SecurityRanking::where('model_run_id', $latestRunId)
                ->whereIn('security_id', $watchlist->securities->pluck('id'))
                ->get(['security_id', 'final_score', 'rank'])
                ->keyBy('security_id');
        }

        $allSecurities = Security::where('is_active', true)
            ->select('id', 'ticker', 'name', 'exchange_id')
            ->with('exchange:id,code')
            ->orderBy('ticker')
            ->get();

        return Inertia::render('Watchlists/Show', [
            'watchlist'     => $watchlist,
            'rankings'      => $rankings,
            'allSecurities' => $allSecurities,
        ]);
    }

    public function addItem(StoreWatchlistItemRequest $request, Watchlist $watchlist): RedirectResponse
    {
        $validated = $request->validated();

        $watchlist->securities()->syncWithoutDetaching([
            $validated['security_id'] => ['notes' => $validated['notes'] ?? null],
        ]);

        return redirect()->back()->with('success', 'Azione aggiunta alla watchlist.');
    }

    public function removeItem(Watchlist $watchlist, Security $security): RedirectResponse
    {
        $this->authorize('update', $watchlist);

        $watchlist->securities()->detach($security->id);

        return redirect()->back()->with('success', 'Azione rimossa dalla watchlist.');
    }
}
