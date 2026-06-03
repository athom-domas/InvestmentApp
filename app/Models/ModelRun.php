<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelRun extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'data_cutoff_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function factorValues(): HasMany
    {
        return $this->hasMany(FactorValue::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(SecurityRanking::class);
    }
}
