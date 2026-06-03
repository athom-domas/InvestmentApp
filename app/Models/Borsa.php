<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borsa extends Model
{
    protected $table = 'borse';
    protected $guarded = ['id'];

    public function titoli(): HasMany
    {
        return $this->hasMany(Titolo::class, 'borsa_id');
    }
}
