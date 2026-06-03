<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SorgenteDati extends Model
{
    protected $table = 'sorgenti_dati';
    protected $guarded = ['id'];

    protected $casts = [
        'attivo' => 'boolean',
        'metadati' => 'array',
    ];
}
