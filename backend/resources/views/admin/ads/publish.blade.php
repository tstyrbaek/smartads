<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Publicér annonce
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-6 flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-600">Annonce</div>
                    <div class="font-semibold">{{ $ad->title ?? $ad->id }}</div>
                    <div class="text-xs text-gray-500">Company: {{ $ad->company?->name ?? $ad->company_id }}</div>
                </div>

                <a href="{{ route('admin.ads.index', ['company_id' => $ad->company_id]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Tilbage</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.ads.publish.update', $ad) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <div class="font-medium text-gray-900">Website embeds</div>
                            <div class="mt-1 text-sm text-gray-500">Vælg hvilke integration-instanser annoncen skal være synlig på.</div>
                        </div>

                        <div class="space-y-3">
                            @forelse($instances as $instance)
                                @php
                                    $checked = in_array($instance->id, old('instance_ids', $selectedInstanceIds ?? []), true);
                                    $token = (string) ($instance->config['embed_token'] ?? '');
                                    $hasToken = $token !== '';
                                @endphp

                                <label class="flex items-start gap-3 rounded-md border p-3">
                                    <input type="checkbox" name="instance_ids[]" value="{{ $instance->id }}" @checked($checked) class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900">{{ $instance->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $instance->integration_key }}</div>

                                        @if(!$hasToken)
                                            <div class="mt-2 text-xs text-gray-600">Embed token mangler. Gå til company og rediger integrationen.</div>
                                        @endif
                                    </div>
                                </label>
                            @empty
                                <div class="text-sm text-gray-500">Der er ingen aktive integrationer på denne company endnu.</div>
                            @endforelse
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">Gem</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
