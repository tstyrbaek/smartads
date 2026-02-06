<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyTokenUsage extends Model
{
    use HasFactory;

    protected $table = 'monthly_token_usage';

    protected $fillable = [
        'company_id',
        'year',
        'month',
        'total_tokens',
        'total_cost_dkk',
    ];

    protected $casts = [
        'total_cost_dkk' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function getCurrentMonthTokens(): int
    {
        return $this->total_tokens;
    }

    public function getFormattedCostAttribute(): string
    {
        return number_format($this->total_cost_dkk, 2, ',', '.') . ' kr.';
    }

    public function getFormattedTokensAttribute(): string
    {
        return number_format($this->total_tokens, 0, ',', '.');
    }
}
