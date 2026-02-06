<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function create(Company $company): View|RedirectResponse
    {
        if ($company->subscriptions()->exists()) {
            return redirect()
                ->route('admin.companies.edit', $company)
                ->with('error', 'Company har allerede et abonnement');
        }

        return view('admin.companies.subscriptions.create', [
            'company' => $company->loadMissing('subscription.plan', 'subscriptions.plan'),
            'plans' => SubscriptionPlan::active()->get(),
        ]);
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        if ($company->subscriptions()->exists()) {
            return redirect()
                ->route('admin.companies.edit', $company)
                ->with('error', 'Company har allerede et abonnement');
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'auto_renew' => ['boolean'],
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        $subscription = $this->subscriptionService->createSubscription(
            $company,
            $plan,
            now()->parse($validated['starts_at']),
            $validated['ends_at'] ? now()->parse($validated['ends_at']) : null
        );

        if ($request->boolean('auto_renew')) {
            $subscription->update(['auto_renew' => true]);
        } else {
            $subscription->update(['auto_renew' => false]);
        }

        return redirect()
            ->route('admin.companies.edit', $company)
            ->with('success', 'Abonnement oprettet succesfuldt');
    }
}
