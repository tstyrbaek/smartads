<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brand extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'logo_path',
        'color_1',
        'color_2',
        'color_3',
        'color_4',
        'fonts',
        'slogan',
        'visual_guidelines',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
