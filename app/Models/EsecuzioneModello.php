<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EsecuzioneModello extends Model
{
    protected $table = 'esecuzioni_modello';
    protected $guarded = ['id'];

    protected $casts = [
        'data_taglio' => 'datetime',
        'iniziato_a' => 'datetime',
        'terminato_a' => 'datetime',
        'metadati' => 'array',
    ];

    public function valoriFattori(): HasMany
    {
        return $this->hasMany(ValoreFattore::class, 'esecuzione_modello_id');
    }

    public function classifiche(): HasMany
    {
        return $this->hasMany(ClassificaTitolo::class, 'esecuzione_modello_id');
    }
}
