<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ads
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <form method="GET" action="{{ route('admin.ads.index') }}" class="flex items-end gap-4">
                    <div>
                        <label for="company_id" class="block text-sm font-medium text-gray-700">Company</label>
                        <select id="company_id" name="company_id" class="mt-1 block w-72 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) $selectedCompanyId === (string) $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                            Filter
                        </button>
                        <a href="{{ route('admin.ads.index') }}" class="text-sm text-gray-700 hover:text-gray-900">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <th class="py-2 pr-4">Preview</th>
                                    <th class="py-2 pr-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'company', 'dir' => ($sort === 'company' && $dir === 'asc') ? 'desc' : 'asc']) }}" class="hover:text-gray-700">Company</a>
                                    </th>
                                    <th class="py-2 pr-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'user', 'dir' => ($sort === 'user' && $dir === 'asc') ? 'desc' : 'asc']) }}" class="hover:text-gray-700">Username</a>
                                    </th>
                                    <th class="py-2 pr-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'dir' => ($sort === 'created_at' && $dir === 'asc') ? 'desc' : 'asc']) }}" class="hover:text-gray-700">Created</a>
                                    </th>
                                    <th class="py-2 pr-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'total_tokens', 'dir' => ($sort === 'total_tokens' && $dir === 'asc') ? 'desc' : 'asc']) }}" class="hover:text-gray-700">Tokens</a>
                                    </th>
                                    <th class="py-2 pr-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'dir' => ($sort === 'status' && $dir === 'asc') ? 'desc' : 'asc']) }}" class="hover:text-gray-700">Status</a>
                                    </th>
                                    <th class="py-2 pr-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($ads as $ad)
                                    <tr>
                                        <td class="py-2 pr-4">
                                            @if (is_string($ad->local_file_path) && $ad->local_file_path !== '')
                                                <a href="{{ asset('storage/' . $ad->local_file_path) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $ad->local_file_path) }}" alt="Ad preview" class="h-16 w-16 rounded object-cover border border-gray-200" />
                                                </a>
                                            @else
                                                <div class="h-16 w-16 rounded bg-gray-100 border border-gray-200"></div>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4">
                                            <div class="text-sm text-gray-900">{{ $ad->company?->name ?? $ad->company_id }}</div>
                                        </td>
                                        <td class="py-2 pr-4 text-sm">{{ $ad->user?->name ?? '-' }}</td>
                                        <td class="py-2 pr-4 text-sm">{{ $ad->created_at }}</td>
                                        <td class="py-2 pr-4 text-sm">
                                            {{ $ad->total_tokens ?? '-' }}
                                            @if (is_numeric($ad->estimated_price_dkk))
                                                ({{ number_format((float) $ad->estimated_price_dkk, 2, ',', '.') }} kr)
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4 text-sm">{{ $ad->status }}</td>
                                        <td class="py-2 pr-4 text-sm text-right">
                                            <a href="{{ route('admin.ads.publish.edit', $ad) }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-700 border border-gray-300 hover:bg-gray-50">Publicér</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $ads->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
