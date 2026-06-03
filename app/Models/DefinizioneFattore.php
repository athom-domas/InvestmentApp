<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefinizioneFattore extends Model
{
    protected $table = 'definizioni_fattori';
    protected $guarded = ['id'];

    protected $casts = [
        'attivo' => 'boolean',
    ];
}
