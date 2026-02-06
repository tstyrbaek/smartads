<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Opret Abonnement
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('admin.subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Tilbage</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('admin.subscriptions.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="company_id">Company *</label>
                            <select id="company_id" name="company_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Vælg company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}@if($company->hasActiveSubscription()) (Har allerede abonnement)@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="plan_id">Abonnementspakke *</label>
                            <select id="plan_id" name="plan_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Vælg pakke</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" data-tokens="{{ $plan->max_tokens_per_month }}" data-price="{{ $plan->price_per_month }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} - {{ $plan->formatted_price }} ({{ number_format($plan->max_tokens_per_month, 0, ',', '.') }} tokens)
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-medium text-sm text-gray-700" for="starts_at">Start dato *</label>
                                <input id="starts_at" name="starts_at" type="date" value="{{ old('starts_at', now()->format('Y-m-d')) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                @error('starts_at')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block font-medium text-sm text-gray-700" for="ends_at">Slut dato (valgfri)</label>
                                <input id="ends_at" name="ends_at" type="date" value="{{ old('ends_at') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                <p class="mt-1 text-xs text-gray-500">Lad være tomt for ubegrænset abonnement</p>
                                @error('ends_at')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <input id="auto_renew" name="auto_renew" type="checkbox" value="1" checked class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <div>
                                <label class="text-sm text-gray-700" for="auto_renew">Auto-renew</label>
                                <p class="text-xs text-gray-500">Abonnementet fornyes automatisk ved udløb</p>
                            </div>
                        </div>

                        <div id="plan-summary" class="hidden rounded-md bg-indigo-50 p-4 text-sm text-indigo-900">
                            <div class="font-medium">Abonnement oversigt</div>
                            <div id="plan-details" class="mt-1 text-sm"></div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Opret</button>
                            <a href="{{ route('admin.subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Annuller</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const planSelect = document.getElementById('plan_id');
    const companySelect = document.getElementById('company_id');
    const planSummary = document.getElementById('plan-summary');
    const planDetails = document.getElementById('plan-details');

    function updatePlanSummary() {
        const selectedOption = planSelect.options[planSelect.selectedIndex];
        const tokens = selectedOption.dataset.tokens;
        const price = selectedOption.dataset.price;

        if (tokens && price) {
            planDetails.innerHTML = `
                <strong>Pakke:</strong> ${selectedOption.text}<br>
                <strong>Tokens pr. måned:</strong> ${parseInt(tokens).toLocaleString('da-DK')}<br>
                <strong>Pris pr. måned:</strong> ${parseFloat(price).toLocaleString('da-DK', {style: 'currency', currency: 'DKK'})}
            `;
            planSummary.classList.remove('hidden');
        } else {
            planSummary.classList.add('hidden');
        }
    }

    function checkCompanySubscription() {
        const selectedOption = companySelect.options[companySelect.selectedIndex];
        const hasSubscription = selectedOption.textContent.includes('(Har allerede abonnement)');
        
        if (hasSubscription) {
            if (!confirm('Denne company har allerede et aktivt abonnement. Er du sikker på du vil oprette et nyt?')) {
                companySelect.value = '';
            }
        }
    }

    planSelect.addEventListener('change', updatePlanSummary);
    companySelect.addEventListener('change', checkCompanySubscription);

    // Set minimum date to today
    const startsAt = document.getElementById('starts_at');
    const endsAt = document.getElementById('ends_at');
    const today = new Date().toISOString().split('T')[0];
    
    startsAt.min = today;
    endsAt.min = today;

    // Update ends_at minimum when starts_at changes
    startsAt.addEventListener('change', function() {
        endsAt.min = this.value;
        if (endsAt.value && endsAt.value < this.value) {
            endsAt.value = this.value;
        }
    });
});
</script>

</x-app-layout>
