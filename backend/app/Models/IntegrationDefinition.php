<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationDefinition extends Model
{
    protected $fillable = [
        'key',
        'type',
        'name',
        'description',
        'capabilities',
        'is_active',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'is_active' => 'boolean',
    ];
}
