<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function createSubscription(Company $company, SubscriptionPlan $plan, Carbon $startDate, ?Carbon $endDate = null): Subscription
    {
        // Deactivate existing subscription if any
        $this->deactivateExistingSubscription($company);

        $subscription = Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'is_active' => true,
            'auto_renew' => true,
        ]);

        // Update company's current subscription
        $company->subscription_id = $subscription->id;
        $company->save();

        Log::info('Subscription created', [
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    public function canUpgradeSubscription(Company $company, SubscriptionPlan $newPlan): bool
    {
        $currentSubscription = $company->getCurrentSubscription();
        
        if (!$currentSubscription) {
            return true;
        }

        return $newPlan->max_tokens_per_month > $currentSubscription->plan->max_tokens_per_month;
    }

    public function upgradeSubscription(Company $company, SubscriptionPlan $newPlan): Subscription
    {
        $currentSubscription = $company->getCurrentSubscription();
        
        if ($currentSubscription) {
            $oldPlanId = $currentSubscription->plan_id;
            // Keep the same billing cycle but upgrade the plan
            $currentSubscription->update([
                'plan_id' => $newPlan->id,
            ]);

            Log::info('Subscription upgraded', [
                'company_id' => $company->id,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlan->id,
            ]);

            return $currentSubscription->fresh();
        }

        // Create new subscription if none exists
        return $this->createSubscription($company, $newPlan, now());
    }

    public function cancelSubscription(Company $company): bool
    {
        $subscription = $company->getCurrentSubscription();
        
        if (!$subscription) {
            return false;
        }

        $subscription->update([
            'is_active' => false,
            'auto_renew' => false,
        ]);

        // Remove company reference
        $company->subscription_id = null;
        $company->save();

        Log::info('Subscription cancelled', [
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
        ]);

        return true;
    }

    public function renewSubscription(Subscription $subscription): Subscription
    {
        $plan = $subscription->plan;
        $newEndDate = $subscription->ends_at ? $subscription->ends_at->addMonth() : now()->addMonth();

        $newSubscription = Subscription::create([
            'company_id' => $subscription->company_id,
            'plan_id' => $plan->id,
            'starts_at' => $subscription->ends_at ?? now(),
            'ends_at' => $newEndDate,
            'is_active' => true,
            'auto_renew' => $subscription->auto_renew,
        ]);

        // Update company's current subscription
        $subscription->company->subscription_id = $newSubscription->id;
        $subscription->company->save();

        Log::info('Subscription renewed', [
            'old_subscription_id' => $subscription->id,
            'new_subscription_id' => $newSubscription->id,
        ]);

        return $newSubscription;
    }

    public function checkAndHandleExpiredSubscriptions(): int
    {
        $expiredCount = Subscription::where('is_active', true)
            ->where('ends_at', '<', now())
            ->update(['is_active' => false]);

        if ($expiredCount > 0) {
            Log::info('Expired subscriptions deactivated', ['count' => $expiredCount]);
        }

        return $expiredCount;
    }

    public function getSubscriptionStatus(Company $company): array
    {
        $subscription = $company->getCurrentSubscription();

        if (!$subscription) {
            return [
                'status' => 'none',
                'plan' => null,
                'remaining_days' => null,
                'tokens_remaining' => 0,
                'tokens_limit' => 0,
            ];
        }

        $tokenService = app(TokenUsageService::class);

        return [
            'status' => $subscription->isExpired() ? 'expired' : 'active',
            'plan' => $subscription->plan,
            'remaining_days' => $subscription->getRemainingDaysAttribute(),
            'tokens_remaining' => $tokenService->getRemainingTokens($company),
            'tokens_limit' => $subscription->plan->max_tokens_per_month,
            'usage_percentage' => $tokenService->getUsagePercentage($company),
        ];
    }

    private function deactivateExistingSubscription(Company $company): void
    {
        $existingSubscription = $company->getCurrentSubscription();
        
        if ($existingSubscription) {
            $existingSubscription->update(['is_active' => false]);
        }
    }
}
