<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElementoWatchlist extends Model
{
    protected $table = 'elementi_watchlist';
    protected $guarded = ['id'];

    public function watchlist(): BelongsTo
    {
        return $this->belongsTo(Watchlist::class, 'watchlist_id');
    }

    public function titolo(): BelongsTo
    {
        return $this->belongsTo(Titolo::class, 'titolo_id');
    }
}
