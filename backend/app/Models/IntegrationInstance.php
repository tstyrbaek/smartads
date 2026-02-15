<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class IntegrationInstance extends Model
{
    protected $fillable = [
        'company_id',
        'integration_key',
        'name',
        'is_active',
        'config',
        'credentials',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'credentials' => 'encrypted:array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ads(): BelongsToMany
    {
        return $this->belongsToMany(Ad::class, 'ad_integration_instance', 'integration_instance_id', 'ad_id')
            ->withPivot(['status', 'published_at', 'meta'])
            ->withTimestamps();
    }
}
