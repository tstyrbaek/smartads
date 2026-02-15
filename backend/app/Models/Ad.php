<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ad extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'user_id',
        'title',
        'text',
        'instructions',
        'prompt',
        'prompt_version',
        'brand_snapshot',
        'prompt_tokens',
        'output_tokens',
        'total_tokens',
        'status',
        'error',
        'local_file_path',
        'image_width',
        'image_height',
        'input_image_paths',
        'debug',
    ];

    protected $casts = [
        'input_image_paths' => 'array',
        'brand_snapshot' => 'array',
        'debug' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integrationInstances(): BelongsToMany
    {
        return $this->belongsToMany(IntegrationInstance::class, 'ad_integration_instance', 'ad_id', 'integration_instance_id')
            ->withPivot(['status', 'published_at', 'meta'])
            ->withTimestamps();
    }
}
