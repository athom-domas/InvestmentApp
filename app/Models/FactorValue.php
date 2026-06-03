<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactorValue extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function modelRun(): BelongsTo
    {
        return $this->belongsTo(ModelRun::class);
    }

    public function security(): BelongsTo
    {
        return $this->belongsTo(Security::class);
    }
}
