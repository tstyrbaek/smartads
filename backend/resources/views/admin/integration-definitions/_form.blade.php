<div class="space-y-6">
    <div>
        <label class="block font-medium text-sm text-gray-700" for="key">Key *</label>
        <input id="key" name="key" type="text" value="{{ old('key', $definition->key) }}" required @disabled(($inUseCount ?? 0) > 0) class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed" />
        @if(($inUseCount ?? 0) > 0)
            <p class="mt-1 text-xs text-gray-500">Key kan ikke ændres, når integrationstypen er i brug.</p>
        @endif
        @error('key')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700" for="type">Type *</label>
        <input id="type" name="type" type="text" value="{{ old('type', $definition->type) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        @error('type')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700" for="name">Navn *</label>
        <input id="name" name="name" type="text" value="{{ old('name', $definition->name) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700" for="description">Beskrivelse</label>
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $definition->description) }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700" for="capabilities">Capabilities (JSON array)</label>
        <textarea id="capabilities" name="capabilities" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('capabilities', is_array($definition->capabilities) ? json_encode($definition->capabilities) : '') }}</textarea>
        @error('capabilities')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0" />
        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $definition->is_active)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
        <label class="text-sm text-gray-700" for="is_active">Aktiv</label>
    </div>
</div>
