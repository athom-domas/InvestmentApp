<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settore extends Model
{
    protected $table = 'settori';
    protected $guarded = ['id'];

    public function industrie(): HasMany
    {
        return $this->hasMany(Industria::class, 'settore_id');
    }

    public function titoli(): HasMany
    {
        return $this->hasMany(Titolo::class, 'settore_id');
    }
}
