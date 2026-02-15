<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\IntegrationInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdPublishController extends Controller
{
    public function edit(Ad $ad): View
    {
        $ad->loadMissing(['company', 'integrationInstances']);

        $instances = IntegrationInstance::query()
            ->where('company_id', $ad->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selected = $ad->integrationInstances->pluck('id')->map(fn ($id) => (int) $id)->all();

        return view('admin.ads.publish', [
            'ad' => $ad,
            'instances' => $instances,
            'selectedInstanceIds' => $selected,
        ]);
    }

    public function update(Request $request, Ad $ad): RedirectResponse
    {
        $validated = $request->validate([
            'instance_ids' => ['nullable', 'array'],
            'instance_ids.*' => ['integer', 'exists:integration_instances,id'],
        ]);

        $instanceIds = collect($validated['instance_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();

        // Ensure instances belong to the same company as the ad
        if (!empty($instanceIds)) {
            $count = IntegrationInstance::query()
                ->whereIn('id', $instanceIds)
                ->where('company_id', $ad->company_id)
                ->count();

            if ($count !== count($instanceIds)) {
                return redirect()->back()->withInput()->with('error', 'Ugyldigt valg af integration');
            }
        }

        $ad->integrationInstances()->syncWithPivotValues($instanceIds, ['status' => 'selected']);

        return redirect()->route('admin.ads.publish.edit', $ad)->with('success', 'Publicering opdateret');
    }
}
