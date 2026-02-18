<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rediger integrationstype
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-6">
                <a href="{{ route('admin.integration-definitions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Tilbage</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.integration-definitions.update', $definition) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        @include('admin.integration-definitions._form', ['definition' => $definition, 'inUseCount' => $inUseCount ?? 0])

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">Gem</button>
                                <a href="{{ route('admin.integration-definitions.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Annuller</a>
                            </div>
                        </div>

                    </form>

                    @if(($definition->type ?? '') === 'network_website_embed')
                        <div class="mt-8 rounded-lg border bg-gray-50 p-4">
                            <div class="text-sm font-semibold text-gray-900">Network embed</div>
                            <p class="mt-1 text-xs text-gray-600">Brug embed-koden på netværkets website. Viser alle annoncer der er udgivet til denne integration.</p>

                            <div class="mt-4 grid gap-2">
                                <label class="text-sm font-medium" for="embedPublicId">Public ID (UUID)</label>
                                <input id="embedPublicId" readonly value="{{ (string) ($embedPublicId ?? '') }}" class="w-full rounded border px-3 py-2 font-mono text-xs" />
                            </div>

                            <div class="mt-4 grid gap-2">
                                <label class="text-sm font-medium" for="embedCode">Embed-kode</label>
                                <textarea id="embedCode" readonly class="min-h-16 w-full rounded border px-3 py-2 font-mono text-xs">{{ (string) ($embedCode ?? '') }}</textarea>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex justify-end">
                        <form method="POST" action="{{ route('admin.integration-definitions.destroy', $definition) }}" onsubmit="return confirm('Er du sikker?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" @disabled(($inUseCount ?? 0) > 0) class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">Slet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
