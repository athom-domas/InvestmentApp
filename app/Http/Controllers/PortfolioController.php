<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortfolioPositionRequest;
use App\Http\Requests\StorePortfolioRequest;
use App\Http\Requests\UpdatePortfolioPositionRequest;
use App\Models\ModelRun;
use App\Models\Portfolio;
use App\Models\PortfolioPosition;
use App\Models\PriceBar;
use App\Models\Security;
use App\Models\SecurityRanking;
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
            'positions.security:id,ticker,name,exchange_id,sector_id,currency',
            'positions.security.exchange:id,code',
            'positions.security.sector:id,name',
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
            )->pluck('price_bars.close', 'price_bars.security_id')
             ->map(fn($v) => (float) $v);
        }

        // Enrich positions with estimated_value
        $portfolio->positions->each(function ($position) use ($latestPrices) {
            $price = $latestPrices->get($position->security_id);
            $position->estimated_value = $price !== null
                ? round((float) $position->quantity * $price, 2)
                : null;
        });

        $totalValue = (float) $portfolio->positions->sum(fn($p) => $p->estimated_value ?? 0);

        // Enrich with percentage weight
        $portfolio->positions->each(function ($position) use ($totalValue) {
            $position->weight = ($totalValue > 0 && $position->estimated_value !== null)
                ? round($position->estimated_value / $totalValue * 100, 2)
                : null;
        });

        // Rankings for portfolio securities
        $latestRunId = ModelRun::where('status', 'completed')->latest()->value('id');
        $rankings = collect();
        if ($latestRunId && $securityIds->isNotEmpty()) {
            $rankings = SecurityRanking::where('model_run_id', $latestRunId)
                ->whereIn('security_id', $securityIds)
                ->get(['security_id', 'final_score', 'rank'])
                ->keyBy('security_id');
        }

        // Weighted average score
        $weightedScore = null;
        if ($totalValue > 0) {
            $acc = $portfolio->positions
                ->filter(fn($p) => $p->estimated_value !== null && $rankings->has($p->security_id))
                ->reduce(fn($carry, $p) => [
                    'sum'   => $carry['sum']   + (float) $rankings->get($p->security_id)->final_score * $p->estimated_value,
                    'total' => $carry['total'] + $p->estimated_value,
                ], ['sum' => 0.0, 'total' => 0.0]);

            if ($acc['total'] > 0) {
                $weightedScore = round($acc['sum'] / $acc['total'], 2);
            }
        }

        // Sector exposure
        $bySector = $portfolio->positions
            ->filter(fn($p) => $p->estimated_value !== null && $p->security?->sector?->name)
            ->groupBy(fn($p) => $p->security->sector->name)
            ->map(fn($group, $name) => [
                'name'   => $name,
                'weight' => $totalValue > 0 ? round($group->sum('estimated_value') / $totalValue * 100, 1) : 0.0,
            ])
            ->sortByDesc('weight')
            ->values();

        // Exchange exposure
        $byExchange = $portfolio->positions
            ->filter(fn($p) => $p->estimated_value !== null && $p->security?->exchange?->code)
            ->groupBy(fn($p) => $p->security->exchange->code)
            ->map(fn($group, $code) => [
                'name'   => $code,
                'weight' => $totalValue > 0 ? round($group->sum('estimated_value') / $totalValue * 100, 1) : 0.0,
            ])
            ->sortByDesc('weight')
            ->values();

        // Currency exposure
        $byCurrency = $portfolio->positions
            ->filter(fn($p) => $p->estimated_value !== null && $p->currency)
            ->groupBy('currency')
            ->map(fn($group, $currency) => [
                'name'   => $currency,
                'weight' => $totalValue > 0 ? round($group->sum('estimated_value') / $totalValue * 100, 1) : 0.0,
            ])
            ->sortByDesc('weight')
            ->values();

        // Top 5 positions by estimated value
        $top5 = $portfolio->positions
            ->filter(fn($p) => $p->estimated_value !== null)
            ->sortByDesc('estimated_value')
            ->take(5)
            ->map(fn($p) => [
                'ticker' => $p->security?->ticker,
                'weight' => $p->weight,
            ])
            ->values();

        // Concentration warnings
        $warnings = [];
        $portfolio->positions->each(function ($p) use (&$warnings) {
            if ($p->weight !== null && $p->weight > 25) {
                $ticker = $p->security?->ticker ?? 'N/D';
                $warnings[] = "La posizione in {$ticker} pesa oltre il 25% del valore stimato del portafoglio.";
            }
        });
        foreach ($bySector as $sector) {
            if ($sector['weight'] > 40) {
                $warnings[] = "Il portafoglio risulta concentrato nel settore {$sector['name']} ({$sector['weight']}%).";
            }
        }
        $missingCount = $portfolio->positions->filter(fn($p) => $p->estimated_value === null)->count();
        if ($missingCount > 0) {
            $label = $missingCount === 1 ? 'posizione' : 'posizioni';
            $warnings[] = "Per {$missingCount} {$label} non sono disponibili prezzi aggiornati.";
        }

        $analytics = [
            'totalValue'    => $totalValue > 0 ? $totalValue : null,
            'positionCount' => $portfolio->positions->count(),
            'weightedScore' => $weightedScore,
            'bySector'      => $bySector,
            'byExchange'    => $byExchange,
            'byCurrency'    => $byCurrency,
            'top5'          => $top5,
            'warnings'      => $warnings,
        ];

        $allSecurities = Security::where('is_active', true)
            ->select('id', 'ticker', 'name', 'exchange_id')
            ->with('exchange:id,code')
            ->orderBy('ticker')
            ->get();

        return Inertia::render('Portfolios/Show', [
            'portfolio'     => $portfolio,
            'latestPrices'  => $latestPrices,
            'rankings'      => $rankings,
            'analytics'     => $analytics,
            'allSecurities' => $allSecurities,
        ]);
    }

    public function addPosition(StorePortfolioPositionRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $portfolio->positions()->create($request->validated());

        return redirect()->back()->with('success', 'Posizione aggiunta.');
    }

    public function updatePosition(UpdatePortfolioPositionRequest $request, Portfolio $portfolio, PortfolioPosition $position): RedirectResponse
    {
        abort_if($position->portfolio_id !== $portfolio->id, 403);

        $position->update($request->validated());

        return redirect()->back()->with('success', 'Posizione aggiornata.');
    }

    public function removePosition(Portfolio $portfolio, PortfolioPosition $position): RedirectResponse
    {
        $this->authorize('update', $portfolio);
        abort_if($position->portfolio_id !== $portfolio->id, 403);

        $position->delete();

        return redirect()->back()->with('success', 'Posizione rimossa.');
    }
}
