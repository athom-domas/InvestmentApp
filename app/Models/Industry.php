<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function securities(): HasMany
    {
        return $this->hasMany(Security::class);
    }
}
