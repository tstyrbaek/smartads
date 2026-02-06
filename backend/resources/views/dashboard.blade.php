<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="{{ route('admin.users.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow transition">
                    <div class="p-6 text-gray-900">
                        <div class="text-sm text-gray-500">Antal brugere</div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($usersCount ?? 0, 0, ',', '.') }}</div>
                    </div>
                </a>

                <a href="{{ route('admin.companies.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow transition">
                    <div class="p-6 text-gray-900">
                        <div class="text-sm text-gray-500">Antal companies</div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($companiesCount ?? 0, 0, ',', '.') }}</div>
                    </div>
                </a>

                <a href="{{ route('admin.ads.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow transition">
                    <div class="p-6 text-gray-900">
                        <div class="text-sm text-gray-500">Antal ads</div>
                        <div class="mt-2 text-3xl font-semibold">{{ number_format($adsCount ?? 0, 0, ',', '.') }}</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
