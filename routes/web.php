<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/rankings', [RankingController::class, 'index'])->name('rankings.index');

    Route::get('/securities/{security}', [SecurityController::class, 'show'])->name('securities.show');

    Route::get('/watchlists', [WatchlistController::class, 'index'])->name('watchlists.index');
    Route::post('/watchlists', [WatchlistController::class, 'store'])->name('watchlists.store');
    Route::get('/watchlists/{watchlist}', [WatchlistController::class, 'show'])->name('watchlists.show');
    Route::post('/watchlists/{watchlist}/items', [WatchlistController::class, 'addItem'])->name('watchlists.items.store');
    Route::delete('/watchlists/{watchlist}/items/{security}', [WatchlistController::class, 'removeItem'])->name('watchlists.items.destroy');

    Route::get('/portfolios', [PortfolioController::class, 'index'])->name('portfolios.index');
    Route::post('/portfolios', [PortfolioController::class, 'store'])->name('portfolios.store');
    Route::get('/portfolios/{portfolio}', [PortfolioController::class, 'show'])->name('portfolios.show');
    Route::post('/portfolios/{portfolio}/positions', [PortfolioController::class, 'addPosition'])->name('portfolios.positions.store');
    Route::put('/portfolios/{portfolio}/positions/{position}', [PortfolioController::class, 'updatePosition'])->name('portfolios.positions.update');
    Route::delete('/portfolios/{portfolio}/positions/{position}', [PortfolioController::class, 'removePosition'])->name('portfolios.positions.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
