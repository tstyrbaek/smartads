<div>
    <label class="block font-medium text-sm text-gray-700" for="level">Level *</label>
    <select id="level" name="level" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @foreach(['info' => 'Info', 'warning' => 'Warning', 'error' => 'Error', 'success' => 'Success'] as $value => $label)
            <option value="{{ $value }}" {{ old('level', $campaign->level ?? 'info') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error('level')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label class="block font-medium text-sm text-gray-700" for="title">Titel *</label>
    <input id="title" name="title" type="text" value="{{ old('title', $campaign->title ?? '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
    @error('title')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label class="block font-medium text-sm text-gray-700" for="message">Besked *</label>
    <textarea id="message" name="message" rows="4" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('message', $campaign->message ?? '') }}</textarea>
    @error('message')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="block font-medium text-sm text-gray-700" for="starts_at">Start *</label>
        <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', isset($campaign->starts_at) ? $campaign->starts_at->format('Y-m-d\\TH:i') : '') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        @error('starts_at')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700" for="ends_at">Slut (valgfri)</label>
        <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', isset($campaign->ends_at) && $campaign->ends_at ? $campaign->ends_at->format('Y-m-d\\TH:i') : '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        @error('ends_at')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="space-y-3">
    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0" />
        <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $campaign->is_active ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
        <label class="text-sm text-gray-700" for="is_active">Aktiv</label>
    </div>

    <div class="flex items-center gap-3">
        <input type="hidden" name="include_inactive_subscriptions" value="0" />
        <input id="include_inactive_subscriptions" name="include_inactive_subscriptions" type="checkbox" value="1" {{ old('include_inactive_subscriptions', $campaign->include_inactive_subscriptions ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
        <label class="text-sm text-gray-700" for="include_inactive_subscriptions">Inkluder inaktive/udløbede abonnementer (ved plan-targeting)</label>
    </div>
</div>

<div>
    <label class="block font-medium text-sm text-gray-700" for="target_mode">Målgruppe *</label>
    <select id="target_mode" name="target_mode" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="all" {{ old('target_mode', $targetMode ?? 'all') === 'all' ? 'selected' : '' }}>Alle companies</option>
        <option value="companies" {{ old('target_mode', $targetMode ?? 'all') === 'companies' ? 'selected' : '' }}>Udvalgte companies</option>
        <option value="plans" {{ old('target_mode', $targetMode ?? 'all') === 'plans' ? 'selected' : '' }}>Companies med udvalgte abonnementer</option>
    </select>
    @error('target_mode')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div id="companies-target" class="space-y-2">
    <label class="block font-medium text-sm text-gray-700">Companies</label>
    <select name="company_ids[]" multiple class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @foreach($companies as $company)
            <option value="{{ $company->id }}" {{ in_array($company->id, old('company_ids', $selectedCompanyIds ?? [])) ? 'selected' : '' }}>{{ $company->name }}</option>
        @endforeach
    </select>
    @error('company_ids')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div id="plans-target" class="space-y-2">
    <label class="block font-medium text-sm text-gray-700">Abonnementspakker</label>
    <select name="plan_ids[]" multiple class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @foreach($plans as $plan)
            <option value="{{ $plan->id }}" {{ in_array($plan->id, old('plan_ids', $selectedPlanIds ?? [])) ? 'selected' : '' }}>{{ $plan->name }}</option>
        @endforeach
    </select>
    @error('plan_ids')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mode = document.getElementById('target_mode');
    const companies = document.getElementById('companies-target');
    const plans = document.getElementById('plans-target');

    function sync() {
        const v = mode.value;
        if (v === 'companies') {
            companies.style.display = '';
            plans.style.display = 'none';
            return;
        }
        if (v === 'plans') {
            companies.style.display = 'none';
            plans.style.display = '';
            return;
        }
        companies.style.display = 'none';
        plans.style.display = 'none';
    }

    mode.addEventListener('change', sync);
    sync();
});
</script>
