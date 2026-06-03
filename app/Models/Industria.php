<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industria extends Model
{
    protected $table = 'industrie';
    protected $guarded = ['id'];

    public function settore(): BelongsTo
    {
        return $this->belongsTo(Settore::class, 'settore_id');
    }

    public function titoli(): HasMany
    {
        return $this->hasMany(Titolo::class, 'industria_id');
    }
}
