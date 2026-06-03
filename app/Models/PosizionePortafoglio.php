<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosizionePortafoglio extends Model
{
    protected $table = 'posizioni_portafoglio';
    protected $guarded = ['id'];

    protected $casts = [
        'aperto_il' => 'date',
    ];

    public function portafoglio(): BelongsTo
    {
        return $this->belongsTo(Portafoglio::class, 'portafoglio_id');
    }

    public function titolo(): BelongsTo
    {
        return $this->belongsTo(Titolo::class, 'titolo_id');
    }
}
