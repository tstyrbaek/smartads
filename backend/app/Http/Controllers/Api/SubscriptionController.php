<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use App\Services\TokenUsageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private TokenUsageService $tokenService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $company = $this->getActiveCompany($request);
        $status = $this->subscriptionService->getSubscriptionStatus($company);

        return response()->json([
            'subscription' => $status,
            'usage_history' => $this->tokenService->getUsageHistory($company),
        ]);
    }

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::active()->get()->map(function ($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'max_tokens_per_month' => $plan->max_tokens_per_month,
                'formatted_tokens' => $plan->formatted_tokens,
                'price_per_month' => $plan->price_per_month,
                'formatted_price' => $plan->formatted_price,
                'features' => $plan->features,
            ];
        });

        return response()->json(['plans' => $plans]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $company = $this->getActiveCompany($request);

        if (!$company) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        try {
            $subscription = $this->subscriptionService->createSubscription(
                $company,
                $plan,
                now(),
                now()->addMonth()
            );

            return response()->json([
                'message' => 'Subscription created successfully',
                'subscription' => $subscription->load('plan'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create subscription'], 500);
        }
    }

    public function upgrade(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $company = $this->getActiveCompany($request);

        if (!$company) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        if (!$this->subscriptionService->canUpgradeSubscription($company, $newPlan)) {
            return response()->json(['error' => 'Cannot downgrade subscription'], 400);
        }

        try {
            $subscription = $this->subscriptionService->upgradeSubscription($company, $newPlan);

            return response()->json([
                'message' => 'Subscription upgraded successfully',
                'subscription' => $subscription->load('plan'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to upgrade subscription'], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        $company = $this->getActiveCompany($request);

        if (!$company) {
            return response()->json(['error' => 'No company found'], 404);
        }

        try {
            $cancelled = $this->subscriptionService->cancelSubscription($company);

            if (!$cancelled) {
                return response()->json(['error' => 'No active subscription found'], 404);
            }

            return response()->json(['message' => 'Subscription cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to cancel subscription'], 500);
        }
    }

    public function usage(Request $request): JsonResponse
    {
        $company = $this->getActiveCompany($request);

        if (!$company) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $subscription = $company->getCurrentSubscription();
        $monthlyUsage = $this->tokenService->getMonthlyUsage($company->id);

        return response()->json([
            'current_subscription' => $subscription?->load('plan'),
            'monthly_usage' => [
                'tokens_used' => $monthlyUsage->total_tokens,
                'formatted_tokens' => $monthlyUsage->formatted_tokens,
                'cost' => $monthlyUsage->total_cost_dkk,
                'formatted_cost' => $monthlyUsage->formatted_cost,
                'period' => $monthlyUsage->year . '-' . str_pad($monthlyUsage->month, 2, '0', STR_PAD_LEFT),
            ],
            'remaining_tokens' => $this->tokenService->getRemainingTokens($company),
            'usage_percentage' => $this->tokenService->getUsagePercentage($company),
        ]);
    }

    public function tokensSummary(Request $request): JsonResponse
    {
        $company = $this->getActiveCompany($request);

        $subscription = $company->getCurrentSubscription();
        if (!$subscription) {
            return response()->json([
                'status' => 'none',
                'period' => now()->format('Y-m'),
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
                'usage_percentage' => 0,
            ]);
        }

        $monthlyUsage = $this->tokenService->getMonthlyUsage($company->id);
        $limit = (int) $subscription->plan->max_tokens_per_month;
        $used = (int) $monthlyUsage->total_tokens;
        $remaining = max(0, $limit - $used);
        $usagePercentage = $limit > 0 ? min(100, ($used / $limit) * 100) : 0;

        return response()->json([
            'status' => 'active',
            'period' => $monthlyUsage->year . '-' . str_pad($monthlyUsage->month, 2, '0', STR_PAD_LEFT),
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'usage_percentage' => $usagePercentage,
        ]);
    }

    public function checkTokens(Request $request): JsonResponse
    {
        $request->validate([
            'estimated_tokens' => 'required|integer|min:1',
        ]);

        $company = $this->getActiveCompany($request);

        if (!$company) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $canGenerate = $this->tokenService->canGenerateAd(
            $company,
            $request->estimated_tokens
        );

        return response()->json([
            'can_generate' => $canGenerate,
            'remaining_tokens' => $this->tokenService->getRemainingTokens($company),
            'estimated_tokens' => $request->estimated_tokens,
        ]);
    }

    private function getActiveCompany(Request $request): Company
    {
        $activeCompanyId = (int) $request->attributes->get('active_company_id');

        return Company::query()
            ->with(['subscription.plan'])
            ->findOrFail($activeCompanyId);
    }
}
