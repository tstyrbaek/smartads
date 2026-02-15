<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Company extends Model
{
    protected $fillable = [
        'name',
        'website_url',
        'company_description',
        'target_audience_description',
    ];

    public function brand(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Brand::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function integrationInstances(): HasMany
    {
        return $this->hasMany(IntegrationInstance::class);
    }

    public function tokenUsage(): HasMany
    {
        return $this->hasMany(TokenUsage::class);
    }

    public function monthlyTokenUsage(): HasMany
    {
        return $this->hasMany(MonthlyTokenUsage::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function getCurrentSubscription(): ?Subscription
    {
        $subscription = $this->subscription;

        if (!$subscription) {
            return null;
        }

        if (!$subscription->is_active) {
            return null;
        }

        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            return null;
        }

        return $subscription;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->getCurrentSubscription() !== null;
    }

    public function getRemainingTokensThisMonth(): int
    {
        $subscription = $this->getCurrentSubscription();
        if (!$subscription) {
            return 0;
        }

        $monthlyUsage = $this->monthlyTokenUsage()
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();

        $used = $monthlyUsage?->total_tokens ?? 0;
        $max = $subscription->plan->max_tokens_per_month;

        return max(0, $max - $used);
    }
}
