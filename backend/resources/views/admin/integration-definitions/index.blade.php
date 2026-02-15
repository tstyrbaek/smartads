<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Integrationstyper
            </h2>

            <a href="{{ route('admin.integration-definitions.create') }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">Opret integrationstype</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <th class="py-2 pr-4">Navn</th>
                                    <th class="py-2 pr-4">Key</th>
                                    <th class="py-2 pr-4">Type</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($definitions as $definition)
                                    <tr>
                                        <td class="py-2 pr-4 text-sm font-medium text-gray-900">{{ $definition->name }}</td>
                                        <td class="py-2 pr-4 text-sm text-gray-900">{{ $definition->key }}</td>
                                        <td class="py-2 pr-4 text-sm text-gray-900">{{ $definition->type }}</td>
                                        <td class="py-2 pr-4 text-sm">
                                            @if($definition->is_active)
                                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktiv</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20">Inaktiv</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4 text-sm text-right">
                                            <a href="{{ route('admin.integration-definitions.edit', $definition) }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-700 border border-gray-300 hover:bg-gray-50">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $definitions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
