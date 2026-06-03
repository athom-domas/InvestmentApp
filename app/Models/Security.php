<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Security extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function priceBars(): HasMany
    {
        return $this->hasMany(PriceBar::class);
    }

    public function fundamentals(): HasMany
    {
        return $this->hasMany(Fundamental::class);
    }

    public function factorValues(): HasMany
    {
        return $this->hasMany(FactorValue::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(SecurityRanking::class);
    }
}
