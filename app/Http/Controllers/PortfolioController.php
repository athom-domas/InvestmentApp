<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortfolioPositionRequest;
use App\Http\Requests\StorePortfolioRequest;
use App\Http\Requests\UpdatePortfolioPositionRequest;
use App\Models\Portfolio;
use App\Models\PortfolioPosition;
use App\Models\PriceBar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    public function index(Request $request): Response
    {
        $portfolios = $request->user()
            ->portfolios()
            ->withCount('positions')
            ->orderBy('name')
            ->get();

        return Inertia::render('Portfolios/Index', [
            'portfolios' => $portfolios,
        ]);
    }

    public function store(StorePortfolioRequest $request): RedirectResponse
    {
        $portfolio = $request->user()->portfolios()->create($request->validated());

        return redirect()->route('portfolios.show', $portfolio);
    }

    public function show(Portfolio $portfolio): Response
    {
        $this->authorize('view', $portfolio);

        $portfolio->load([
            'positions:id,portfolio_id,security_id,quantity,average_price,currency,opened_at,notes',
            'positions.security:id,ticker,name,exchange_id,currency',
            'positions.security.exchange:id,code',
        ]);

        $securityIds = $portfolio->positions->pluck('security_id')->filter()->unique();
        $latestPrices = collect();
        if ($securityIds->isNotEmpty()) {
            $subquery = PriceBar::selectRaw('security_id, MAX(date) as max_date')
                ->whereIn('security_id', $securityIds)
                ->groupBy('security_id');

            $latestPrices = PriceBar::joinSub($subquery, 'latest', fn($join) =>
                $join->on('price_bars.security_id', '=', 'latest.security_id')
                     ->on('price_bars.date', '=', 'latest.max_date')
            )
            ->pluck('price_bars.close', 'price_bars.security_id');
        }

        return Inertia::render('Portfolios/Show', [
            'portfolio'    => $portfolio,
            'latestPrices' => $latestPrices,
        ]);
    }

    public function addPosition(StorePortfolioPositionRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $portfolio->positions()->create($request->validated());

        return redirect()->back();
    }

    public function updatePosition(UpdatePortfolioPositionRequest $request, Portfolio $portfolio, PortfolioPosition $position): RedirectResponse
    {
        abort_if($position->portfolio_id !== $portfolio->id, 403);

        $position->update($request->validated());

        return redirect()->back();
    }

    public function removePosition(Portfolio $portfolio, PortfolioPosition $position): RedirectResponse
    {
        $this->authorize('update', $portfolio);
        abort_if($position->portfolio_id !== $portfolio->id, 403);

        $position->delete();

        return redirect()->back();
    }
}
