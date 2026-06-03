<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portafoglio extends Model
{
    protected $table = 'portafogli';
    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posizioni(): HasMany
    {
        return $this->hasMany(PosizionePortafoglio::class, 'portafoglio_id');
    }
}
