<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rediger integration ({{ $company->name }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-6">
                <a href="{{ route('admin.companies.edit', $company) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Tilbage</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.companies.integrations.update', [$company, $instance]) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="integration_key">Integration *</label>
                            <select id="integration_key" name="integration_key" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($definitions as $def)
                                    <option value="{{ $def->key }}" @selected(old('integration_key', $instance->integration_key) === $def->key)>{{ $def->name }}</option>
                                @endforeach
                            </select>
                            @error('integration_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="name">Navn *</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $instance->name) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_active" value="0" />
                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $instance->is_active)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <label class="text-sm text-gray-700" for="is_active">Aktiv</label>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="config_site_url">Website URL (valgfri)</label>
                            <input id="config_site_url" name="config[site_url]" type="url" value="{{ old('config.site_url', $instance->config['site_url'] ?? '') }}" placeholder="https://..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('config.site_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="config_embed_token">Embed token (valgfri)</label>
                            <input id="config_embed_token" name="config[embed_token]" type="text" value="{{ old('config.embed_token', $instance->config['embed_token'] ?? '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('config.embed_token')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="border border-gray-200 rounded-md p-4">
                            <div class="font-medium text-gray-900">Embed</div>
                            <div class="mt-1 text-sm text-gray-500">Embed-koden ligger på integrationen. Denne integration viser alle annoncer, der er valgt til instansen.</div>

                            @php
                                $scriptUrl = route('embed.script', ['instance' => $instance->id]);
                            @endphp

                            <div class="mt-4">
                                <div class="text-xs text-gray-600">Embed-kode</div>
                                <textarea readonly rows="2" class="mt-1 w-full rounded border px-3 py-2 text-xs">&lt;script src=&quot;{{ $scriptUrl }}&quot;&gt;&lt;/script&gt;</textarea>
                            </div>

                            <div class="mt-6">
                                <div class="text-sm font-medium text-gray-900">Annoncer på denne integration</div>
                                <div class="mt-1 text-sm text-gray-500">Du vælger annoncer via admin \"Publicér\" på annoncen.</div>

                                <div class="mt-4 space-y-4">
                                    @forelse($instance->ads as $ad)
                                        @php
                                            $hasImage = is_string($ad->local_file_path) && $ad->local_file_path !== '';
                                            $imageUrl = $hasImage ? asset('storage/' . $ad->local_file_path) : null;
                                        @endphp

                                        @if($hasImage)
                                            <a href="{{ $imageUrl }}" target="_blank" class="block rounded-md border p-3 hover:bg-gray-50">
                                                <div class="text-sm font-medium text-gray-900">{{ $ad->title ?? $ad->id }}</div>
                                                <div class="text-xs text-gray-500">{{ $ad->id }}</div>
                                            </a>
                                        @else
                                            <a href="{{ route('admin.ads.publish.edit', $ad) }}" class="block rounded-md border p-3 hover:bg-gray-50">
                                                <div class="text-sm font-medium text-gray-900">{{ $ad->title ?? $ad->id }}</div>
                                                <div class="text-xs text-gray-500">{{ $ad->id }}</div>
                                            </a>
                                        @endif
                                    @empty
                                        <div class="text-sm text-gray-500">Ingen annoncer tilknyttet endnu.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">Gem</button>
                            <a href="{{ route('admin.companies.edit', $company) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">Annuller</a>
                        </div>
                    </form>

                    <div class="mt-6 flex justify-end">
                        <form method="POST" action="{{ route('admin.companies.integrations.destroy', [$company, $instance]) }}" onsubmit="return confirm('Er du sikker?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">Slet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
