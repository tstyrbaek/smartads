<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Notifikationer
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <a href="{{ route('admin.notifications.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Opret notifikation</a>
            </div>

            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titel</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gyldig</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Målgruppe</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($campaigns as $campaign)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900">
                                            <div class="font-medium">{{ $campaign->title }}</div>
                                            <div class="text-xs text-gray-600 truncate max-w-xl">{{ $campaign->message }}</div>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ $campaign->level }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900">
                                            <div class="text-xs text-gray-600">Start: {{ $campaign->starts_at?->format('Y-m-d H:i') }}</div>
                                            <div class="text-xs text-gray-600">Slut: {{ $campaign->ends_at?->format('Y-m-d H:i') ?? '-' }}</div>
                                            <div class="mt-1">
                                                @if($campaign->is_active)
                                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktiv</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20">Inaktiv</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-900">
                                            @if($campaign->companies_count === 0 && $campaign->subscription_plans_count === 0)
                                                <span class="text-xs text-gray-700">Alle companies</span>
                                            @elseif($campaign->companies_count > 0)
                                                <span class="text-xs text-gray-700">Udvalgte companies ({{ $campaign->companies_count }})</span>
                                            @else
                                                <span class="text-xs text-gray-700">Udvalgte abonnementer ({{ $campaign->subscription_plans_count }})</span>
                                                @if($campaign->include_inactive_subscriptions)
                                                    <div class="text-xs text-gray-600">Inkl. inaktive</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-900 text-right space-x-2">
                                            <a href="{{ route('admin.notifications.edit', $campaign) }}" class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Edit</a>

                                            <form action="{{ route('admin.notifications.destroy', $campaign) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700" onclick="return confirm('Er du sikker?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-sm text-gray-500 text-center">Ingen notifikationer</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $campaigns->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
