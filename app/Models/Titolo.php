<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Titolo extends Model
{
    protected $table = 'titoli';
    protected $guarded = ['id'];

    protected $casts = [
        'metadati' => 'array',
        'attivo' => 'boolean',
    ];

    public function borsa(): BelongsTo
    {
        return $this->belongsTo(Borsa::class, 'borsa_id');
    }

    public function settore(): BelongsTo
    {
        return $this->belongsTo(Settore::class, 'settore_id');
    }

    public function industria(): BelongsTo
    {
        return $this->belongsTo(Industria::class, 'industria_id');
    }

    public function barrePrezzi(): HasMany
    {
        return $this->hasMany(BarraPrezzo::class, 'titolo_id');
    }

    public function fondamentali(): HasMany
    {
        return $this->hasMany(Fondamentale::class, 'titolo_id');
    }

    public function valoriFattori(): HasMany
    {
        return $this->hasMany(ValoreFattore::class, 'titolo_id');
    }

    public function classifiche(): HasMany
    {
        return $this->hasMany(ClassificaTitolo::class, 'titolo_id');
    }
}
