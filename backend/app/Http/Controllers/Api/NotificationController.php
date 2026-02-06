<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\NotificationCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $companyId = (int) $request->attributes->get('active_company_id');
        $company = Company::query()->find($companyId);

        if (!$company) {
            return response()->json(['error' => 'company_required'], 422);
        }

        $limit = (int) ($request->query('limit') ?? 10);

        $now = now();

        $activePlanId = $company->getCurrentSubscription()?->plan_id;
        $latestPlanId = $company->subscriptions()->orderByDesc('starts_at')->orderByDesc('created_at')->value('plan_id');

        $campaigns = NotificationCampaign::query()
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->where(function ($q) use ($companyId, $activePlanId, $latestPlanId) {
                $q
                    // All companies: no targeting rows.
                    ->where(function ($sub) {
                        $sub->whereDoesntHave('companies')->whereDoesntHave('subscriptionPlans');
                    })
                    // Explicit company targeting.
                    ->orWhereHas('companies', function ($sub) use ($companyId) {
                        $sub->where('companies.id', $companyId);
                    })
                    // Plan targeting: active subscription.
                    ->orWhere(function ($sub) use ($activePlanId) {
                        if ($activePlanId === null) {
                            $sub->whereRaw('1 = 0');
                            return;
                        }

                        $sub->whereHas('subscriptionPlans', function ($p) use ($activePlanId) {
                            $p->where('subscription_plans.id', $activePlanId);
                        });
                    })
                    // Plan targeting: include inactive subscriptions uses latest subscription plan.
                    ->orWhere(function ($sub) use ($latestPlanId) {
                        if ($latestPlanId === null) {
                            $sub->whereRaw('1 = 0');
                            return;
                        }

                        $sub->where('include_inactive_subscriptions', true)
                            ->whereHas('subscriptionPlans', function ($p) use ($latestPlanId) {
                                $p->where('subscription_plans.id', $latestPlanId);
                            });
                    });
            })
            ->orderByDesc('starts_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'notifications' => $campaigns->map(function (NotificationCampaign $c) {
                return [
                    'id' => $c->id,
                    'level' => $c->level,
                    'title' => $c->title,
                    'message' => $c->message,
                    'data' => $c->data,
                    'starts_at' => $c->starts_at?->toISOString(),
                    'ends_at' => $c->ends_at?->toISOString(),
                ];
            })->values(),
        ]);
    }
}
