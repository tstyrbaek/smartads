<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificationCampaign extends Model
{
    protected $fillable = [
        'level',
        'title',
        'message',
        'data',
        'starts_at',
        'ends_at',
        'is_active',
        'include_inactive_subscriptions',
    ];

    protected $casts = [
        'data' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'include_inactive_subscriptions' => 'boolean',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'notification_campaign_company', 'campaign_id', 'company_id');
    }

    public function subscriptionPlans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'notification_campaign_subscription_plan', 'campaign_id', 'subscription_plan_id');
    }

    protected function isCurrentlyActive(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (!$this->is_active) {
                    return false;
                }

                $now = now();
                if ($this->starts_at && $this->starts_at->isAfter($now)) {
                    return false;
                }

                if ($this->ends_at && $this->ends_at->isBefore($now)) {
                    return false;
                }

                return true;
            }
        );
    }
}
