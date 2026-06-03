<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Watchlist extends Model
{
    protected $table = 'watchlists';
    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function elementi(): HasMany
    {
        return $this->hasMany(ElementoWatchlist::class, 'watchlist_id');
    }

    public function titoli(): BelongsToMany
    {
        return $this->belongsToMany(Titolo::class, 'elementi_watchlist', 'watchlist_id', 'titolo_id')
            ->withPivot('note')
            ->withTimestamps();
    }
}
