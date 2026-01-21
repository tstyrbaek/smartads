<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    protected $fillable = [
        'name',
    ];

    public function brand(): HasOne
    {
        return $this->hasOne(Brand::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
