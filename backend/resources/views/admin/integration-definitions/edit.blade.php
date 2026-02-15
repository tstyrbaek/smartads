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
