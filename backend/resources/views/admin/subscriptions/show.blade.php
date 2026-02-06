<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Abonnement Detaljer
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ $returnTo ?? route('admin.subscriptions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Tilbage</a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="text-sm font-medium text-gray-900">Abonnement information</div>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-gray-500">Company</dt>
                                <dd class="text-gray-900">{{ $subscription->company->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Pakke</dt>
                                <dd class="text-gray-900">
                                    <div class="font-medium">{{ $subscription->plan->name }}</div>
                                    @if($subscription->plan->description)
                                        <div class="text-xs text-gray-500">{{ $subscription->plan->description }}</div>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Status</dt>
                                <dd>
                                    @if($subscription->is_active)
                                        @if($subscription->isExpired())
                                            <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Udløbet</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktiv</span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20">Inaktiv</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Periode</dt>
                                <dd class="text-gray-900">
                                    {{ $subscription->starts_at->format('d/m/Y') }}
                                    @if($subscription->ends_at)
                                        - {{ $subscription->ends_at->format('d/m/Y') }}
                                    @else
                                        - Ubegrænset
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Auto-renew</dt>
                                <dd class="text-gray-900">{{ $subscription->auto_renew ? 'Ja' : 'Nej' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Oprettet</dt>
                                <dd class="text-gray-900">{{ $subscription->created_at->format('d/m/Y H:i') }}</dd>
                            </div>
                        </dl>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <a href="{{ route('admin.subscriptions.edit', $subscription) . ($returnTo ? '?return_to=' . urlencode($returnTo) : '') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Edit</a>

                            @if($subscription->is_active && !$subscription->isExpired())
                                <form action="{{ route('admin.subscriptions.renew', $subscription) }}" method="POST" class="inline">
                                    @csrf
                                    @if($returnTo)
                                        <input type="hidden" name="return_to" value="{{ $returnTo }}" />
                                    @endif
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Renew</button>
                                </form>
                            @endif

                            <form action="{{ route('admin.subscriptions.destroy', $subscription) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                @if($returnTo)
                                    <input type="hidden" name="return_to" value="{{ $returnTo }}" />
                                @endif
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" onclick="return confirm('Er du sikker på du vil annullere dette abonnement?')">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <div class="text-sm font-medium text-gray-900">Token forbrug</div>

                            @if($usage['status'] === 'none')
                                <div class="mt-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-900">Ingen aktivt abonnement fundet</div>
                            @else
                                @php
                                    $usedTokens = max(0, (int) $usage['tokens_limit'] - (int) $usage['tokens_remaining']);
                                    $barClass = $usage['usage_percentage'] > 80 ? 'bg-red-600' : ($usage['usage_percentage'] > 60 ? 'bg-yellow-500' : 'bg-green-600');
                                @endphp

                                <div class="mt-4">
                                    <div class="flex items-center justify-between text-xs text-gray-600">
                                        <span>{{ number_format($usedTokens, 0, ',', '.') }} brugt</span>
                                        <span>{{ number_format($usage['tokens_limit'], 0, ',', '.') }} total</span>
                                    </div>
                                    <div class="mt-2 h-2 w-full rounded bg-gray-200">
                                        <div class="h-2 rounded {{ $barClass }}" style="width: {{ $usage['usage_percentage'] }}%"></div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-600">{{ round($usage['usage_percentage'], 1) }}% brugt — {{ number_format($usage['tokens_remaining'], 0, ',', '.') }} tilbage</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <div class="text-sm font-medium text-gray-900">Pakke detaljer</div>
                            <div class="mt-4 text-sm">
                                <div class="font-medium">{{ $subscription->plan->name }}</div>
                                @if($subscription->plan->description)
                                    <div class="text-xs text-gray-500">{{ $subscription->plan->description }}</div>
                                @endif
                                <div class="mt-3 text-sm"><span class="text-gray-500">Pris:</span> {{ $subscription->plan->formatted_price }} / md</div>
                                <div class="mt-1 text-sm"><span class="text-gray-500">Token grænse:</span> {{ number_format($subscription->plan->max_tokens_per_month, 0, ',', '.') }} / md</div>

                                @if($subscription->plan->features && count($subscription->plan->features) > 0)
                                    <div class="mt-3">
                                        <div class="text-xs text-gray-500">Features</div>
                                        <ul class="mt-1 list-disc pl-4 space-y-1 text-xs text-gray-700">
                                            @foreach($subscription->plan->features as $feature)
                                                <li>{{ $feature }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($history && count($history) > 0)
                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="text-sm font-medium text-gray-900">Forbrugshistorik (seneste 12 måneder)</div>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tokens brugt</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Omkostninger</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($history as $month)
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ $month['period'] }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ number_format($month['tokens'], 0, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ number_format($month['cost'], 2, ',', '.') }} kr.</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
