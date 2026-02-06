<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\NotificationCampaign;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationCampaignController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index', [
            'campaigns' => NotificationCampaign::query()
                ->withCount(['companies', 'subscriptionPlans'])
                ->orderByDesc('id')
                ->paginate(50),
        ]);
    }

    public function create(): View
    {
        return view('admin.notifications.create', [
            'campaign' => new NotificationCampaign(),
            'companies' => Company::query()->orderBy('name')->get(),
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(),
            'targetMode' => 'all',
            'selectedCompanyIds' => [],
            'selectedPlanIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCampaign($request);

        $campaign = NotificationCampaign::query()->create([
            'level' => $validated['level'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'include_inactive_subscriptions' => (bool) ($validated['include_inactive_subscriptions'] ?? false),
            'data' => null,
        ]);

        $this->syncTargets($campaign, $validated);

        return redirect()->route('admin.notifications.index')->with('success', 'Notifikation oprettet');
    }

    public function edit(NotificationCampaign $campaign): View
    {
        $targetMode = 'all';
        if ($campaign->companies()->exists()) {
            $targetMode = 'companies';
        } elseif ($campaign->subscriptionPlans()->exists()) {
            $targetMode = 'plans';
        }

        return view('admin.notifications.edit', [
            'campaign' => $campaign->load(['companies', 'subscriptionPlans']),
            'companies' => Company::query()->orderBy('name')->get(),
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(),
            'targetMode' => $targetMode,
            'selectedCompanyIds' => $campaign->companies()->pluck('companies.id')->all(),
            'selectedPlanIds' => $campaign->subscriptionPlans()->pluck('subscription_plans.id')->all(),
        ]);
    }

    public function update(Request $request, NotificationCampaign $campaign): RedirectResponse
    {
        $validated = $this->validateCampaign($request);

        $campaign->forceFill([
            'level' => $validated['level'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'include_inactive_subscriptions' => (bool) ($validated['include_inactive_subscriptions'] ?? false),
        ])->save();

        $this->syncTargets($campaign, $validated);

        return redirect()->route('admin.notifications.index')->with('success', 'Notifikation opdateret');
    }

    public function destroy(NotificationCampaign $campaign): RedirectResponse
    {
        $campaign->delete();
        return redirect()->route('admin.notifications.index')->with('success', 'Notifikation slettet');
    }

    private function validateCampaign(Request $request): array
    {
        return $request->validate([
            'level' => ['required', 'in:info,warning,error,success'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'include_inactive_subscriptions' => ['sometimes', 'boolean'],
            'target_mode' => ['required', 'in:all,companies,plans'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
            'plan_ids' => ['nullable', 'array'],
            'plan_ids.*' => ['integer', 'exists:subscription_plans,id'],
        ]);
    }

    private function syncTargets(NotificationCampaign $campaign, array $validated): void
    {
        $mode = $validated['target_mode'];

        if ($mode === 'companies') {
            $campaign->companies()->sync($validated['company_ids'] ?? []);
            $campaign->subscriptionPlans()->sync([]);
            return;
        }

        if ($mode === 'plans') {
            $campaign->subscriptionPlans()->sync($validated['plan_ids'] ?? []);
            $campaign->companies()->sync([]);
            return;
        }

        // all
        $campaign->companies()->sync([]);
        $campaign->subscriptionPlans()->sync([]);
    }
}
