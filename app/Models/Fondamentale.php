<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fondamentale extends Model
{
    protected $table = 'fondamentali';
    protected $guarded = ['id'];

    protected $casts = [
        'data_fine_periodo' => 'date',
        'metadati' => 'array',
    ];

    public function titolo(): BelongsTo
    {
        return $this->belongsTo(Titolo::class, 'titolo_id');
    }
}
