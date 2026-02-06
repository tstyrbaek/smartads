<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        return view('admin.subscription-plans.index', [
            'plans' => SubscriptionPlan::query()->orderBy('price_per_month')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.subscription-plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:subscription_plans,name'],
            'description' => ['nullable', 'string'],
            'max_tokens_per_month' => ['required', 'integer', 'min:1'],
            'price_per_month' => ['required', 'numeric', 'min:0'],
            'features' => ['required', 'array', 'min:1'],
            'features.*' => ['required', 'string', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $features = $validated['features'] ?? [];
        $filteredFeatures = array_filter($features, fn($feature) => !empty(trim($feature)));

        if (count($filteredFeatures) === 0) {
            return back()
                ->withErrors(['features' => 'Mindst én feature er påkrævet'])
                ->withInput();
        }

        SubscriptionPlan::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'max_tokens_per_month' => $validated['max_tokens_per_month'],
            'price_per_month' => $validated['price_per_month'],
            'features' => array_values($filteredFeatures),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.subscription-plans.index')
            ->with('success', 'Abonnementspakke oprettet succesfuldt');
    }

    public function edit(SubscriptionPlan $subscriptionPlan): View
    {
        return view('admin.subscription-plans.edit', [
            'plan' => $subscriptionPlan,
        ]);
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:subscription_plans,name,' . $subscriptionPlan->id],
            'description' => ['nullable', 'string'],
            'max_tokens_per_month' => ['required', 'integer', 'min:1'],
            'price_per_month' => ['required', 'numeric', 'min:0'],
            'features' => ['required', 'array', 'min:1'],
            'features.*' => ['required', 'string', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $features = $validated['features'] ?? [];
        $filteredFeatures = array_filter($features, fn($feature) => !empty(trim($feature)));

        if (count($filteredFeatures) === 0) {
            return back()
                ->withErrors(['features' => 'Mindst én feature er påkrævet'])
                ->withInput();
        }

        $subscriptionPlan->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'max_tokens_per_month' => $validated['max_tokens_per_month'],
            'price_per_month' => $validated['price_per_month'],
            'features' => array_values($filteredFeatures),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.subscription-plans.index')
            ->with('success', 'Abonnementspakke opdateret succesfuldt');
    }

    public function destroy(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        // Check if plan has active subscriptions
        if ($subscriptionPlan->subscriptions()->where('is_active', true)->exists()) {
            return redirect()
                ->route('admin.subscription-plans.index')
                ->with('error', 'Kan ikke slette pakke med aktive abonnementer');
        }

        $subscriptionPlan->delete();

        return redirect()
            ->route('admin.subscription-plans.index')
            ->with('success', 'Abonnementspakke slettet');
    }

    public function toggleActive(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $subscriptionPlan->update([
            'is_active' => !$subscriptionPlan->is_active,
        ]);

        $status = $subscriptionPlan->is_active ? 'aktiveret' : 'deaktiveret';

        return redirect()
            ->route('admin.subscription-plans.index')
            ->with('success', "Abonnementspakke {$status}");
    }
}
