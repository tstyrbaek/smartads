<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use App\Services\TokenUsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private TokenUsageService $tokenService
    ) {}

    public function index(): View
    {
        return view('admin.subscriptions.index', [
            'subscriptions' => Subscription::query()
                ->with(['company', 'plan'])
                ->orderBy('created_at', 'desc')
                ->paginate(50),
            'plans' => SubscriptionPlan::active()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.subscriptions.create', [
            'companies' => Company::query()->orderBy('name')->get(),
            'plans' => SubscriptionPlan::active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'auto_renew' => ['boolean'],
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        $subscription = $this->subscriptionService->createSubscription(
            $company,
            $plan,
            now()->parse($validated['starts_at']),
            $validated['ends_at'] ? now()->parse($validated['ends_at']) : null
        );

        if ($request->boolean('auto_renew')) {
            $subscription->update(['auto_renew' => true]);
        }

        $returnTo = $this->getReturnTo($request);

        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'Abonnement oprettet succesfuldt');
        }

        return redirect()->route('admin.subscriptions.index')->with('success', 'Abonnement oprettet succesfuldt');
    }

    public function edit(Request $request, Subscription $subscription): View
    {
        return view('admin.subscriptions.edit', [
            'subscription' => $subscription->load(['company', 'plan']),
            'plans' => SubscriptionPlan::active()->get(),
            'returnTo' => $this->getReturnTo($request),
        ]);
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'ends_at' => ['nullable', 'date'],
            'auto_renew' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $newPlan = SubscriptionPlan::findOrFail($validated['plan_id']);
        
        if ((int) $validated['plan_id'] !== (int) $subscription->plan_id) {
            // Plan change
            $subscription = $this->subscriptionService->upgradeSubscription($subscription->company, $newPlan);
        }

        $subscription->update([
            'ends_at' => $validated['ends_at'] ? now()->parse($validated['ends_at']) : null,
            'auto_renew' => $request->boolean('auto_renew'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $returnTo = $this->getReturnTo($request);

        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'Abonnement opdateret succesfuldt');
        }

        return redirect()->route('admin.subscriptions.index')->with('success', 'Abonnement opdateret succesfuldt');
    }

    public function destroy(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->subscriptionService->cancelSubscription($subscription->company);

        $returnTo = $this->getReturnTo($request);

        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'Abonnement annulleret');
        }

        return redirect()->route('admin.subscriptions.index')->with('success', 'Abonnement annulleret');
    }

    public function show(Request $request, Subscription $subscription): View
    {
        $company = $subscription->company;
        
        return view('admin.subscriptions.show', [
            'subscription' => $subscription->load(['company', 'plan']),
            'usage' => $this->subscriptionService->getSubscriptionStatus($company),
            'history' => $this->tokenService->getUsageHistory($company, 12),
            'returnTo' => $this->getReturnTo($request),
        ]);
    }

    public function renew(Request $request, Subscription $subscription): RedirectResponse
    {
        if ($subscription->isExpired()) {
            $returnTo = $this->getReturnTo($request);

            return redirect()
                ->route('admin.subscriptions.show', [
                    'subscription' => $subscription,
                    'return_to' => $returnTo,
                ])
                ->with('error', 'Kan ikke forny udløbet abonnement');
        }

        $newSubscription = $this->subscriptionService->renewSubscription($subscription);

        $returnTo = $this->getReturnTo($request);

        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'Abonnement fornyet succesfuldt');
        }

        return redirect()->route('admin.subscriptions.show', $newSubscription)->with('success', 'Abonnement fornyet succesfuldt');
    }

    private function getReturnTo(Request $request): ?string
    {
        $returnTo = $request->input('return_to') ?? $request->query('return_to');

        if (!is_string($returnTo) || $returnTo === '') {
            return null;
        }

        if (Str::startsWith($returnTo, '/')) {
            return $returnTo;
        }

        if (Str::startsWith($returnTo, url('/'))) {
            return $returnTo;
        }

        return null;
    }
}
