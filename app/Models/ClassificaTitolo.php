<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassificaTitolo extends Model
{
    protected $table = 'classifiche_titoli';
    protected $guarded = ['id'];

    protected $casts = [
        'metadati' => 'array',
    ];

    public function esecuzioneModello(): BelongsTo
    {
        return $this->belongsTo(EsecuzioneModello::class, 'esecuzione_modello_id');
    }

    public function titolo(): BelongsTo
    {
        return $this->belongsTo(Titolo::class, 'titolo_id');
    }
}
