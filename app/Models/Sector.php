<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function industries(): HasMany
    {
        return $this->hasMany(Industry::class);
    }

    public function securities(): HasMany
    {
        return $this->hasMany(Security::class);
    }
}
