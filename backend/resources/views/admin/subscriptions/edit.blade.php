<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rediger Abonnement
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ $returnTo ?? route('admin.subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Tilbage</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('admin.subscriptions.update', $subscription) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        @if($returnTo)
                            <input type="hidden" name="return_to" value="{{ $returnTo }}" />
                        @endif

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Company</label>
                            <input type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50" value="{{ $subscription->company->name }}" readonly>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="plan_id">Abonnementspakke *</label>
                            <select id="plan_id" name="plan_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" data-tokens="{{ $plan->max_tokens_per_month }}" data-price="{{ $plan->price_per_month }}" {{ old('plan_id', $subscription->plan_id) == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} - {{ $plan->formatted_price }} ({{ number_format($plan->max_tokens_per_month, 0, ',', '.') }} tokens)
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="ends_at">Slut dato (valgfri)</label>
                            <input id="ends_at" name="ends_at" type="date" value="{{ old('ends_at', $subscription->ends_at?->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p class="mt-1 text-xs text-gray-500">Lad være tomt for ubegrænset abonnement</p>
                            @error('ends_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $subscription->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                <label class="text-sm text-gray-700" for="is_active">Aktiv</label>
                            </div>

                            <div class="flex items-start gap-3">
                                <input id="auto_renew" name="auto_renew" type="checkbox" value="1" {{ old('auto_renew', $subscription->auto_renew) ? 'checked' : '' }} class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                <div>
                                    <label class="text-sm text-gray-700" for="auto_renew">Auto-renew</label>
                                    <p class="text-xs text-gray-500">Abonnementet fornyes automatisk ved udløb</p>
                                </div>
                            </div>
                        </div>

                        <div id="plan-summary" class="rounded-md bg-indigo-50 p-4 text-sm text-indigo-900">
                            <div class="font-medium">Nuværende pakke</div>
                            <div id="plan-details" class="mt-1 text-sm">
                                <strong>Pakke:</strong> {{ $subscription->plan->name }}<br>
                                <strong>Tokens pr. måned:</strong> {{ number_format($subscription->plan->max_tokens_per_month, 0, ',', '.') }}<br>
                                <strong>Pris pr. måned:</strong> {{ $subscription->plan->formatted_price }}
                            </div>
                        </div>

                        <div class="rounded-md bg-yellow-50 p-4 text-sm text-yellow-900">
                            <div class="font-medium">Vigtigt</div>
                            <div class="mt-1">Nedgradering af abonnementer er ikke tilladt. Hvis du vil nedgradere, skal du annullere det nuværende abonnement og oprette et nyt.</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Opdater</button>
                            <a href="{{ $returnTo ?? route('admin.subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Annuller</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const planSelect = document.getElementById('plan_id');
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
        }
    }

    planSelect.addEventListener('change', updatePlanSummary);
});
</script>

</x-app-layout>
