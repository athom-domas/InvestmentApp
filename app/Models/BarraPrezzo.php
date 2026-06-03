<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarraPrezzo extends Model
{
    protected $table = 'barre_prezzi';
    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'date',
    ];

    public function titolo(): BelongsTo
    {
        return $this->belongsTo(Titolo::class, 'titolo_id');
    }
}
