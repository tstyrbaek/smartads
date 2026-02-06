<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Opret Abonnementspakke
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('admin.subscription-plans.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Tilbage</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('admin.subscription-plans.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="name">Navn *</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="description">Beskrivelse</label>
                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-medium text-sm text-gray-700" for="max_tokens_per_month">Tokens pr. måned *</label>
                                <input id="max_tokens_per_month" name="max_tokens_per_month" type="number" min="1" value="{{ old('max_tokens_per_month') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                @error('max_tokens_per_month')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block font-medium text-sm text-gray-700" for="price_per_month">Pris pr. måned (DKK) *</label>
                                <input id="price_per_month" name="price_per_month" type="number" step="0.01" min="0" value="{{ old('price_per_month') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                @error('price_per_month')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input id="is_active" name="is_active" type="checkbox" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <label class="text-sm text-gray-700" for="is_active">Aktiv</label>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Features *</label>
                            <div id="features-container" class="mt-2 space-y-2">
                                <div class="feature-input-group">
                                    <input type="text" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" name="features[]" placeholder="Indtast feature" value="{{ old('features.0') }}" required>
                                    <button type="button" class="remove-feature inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Slet</button>
                                </div>
                            </div>

                            @if ($errors->has('features') || $errors->has('features.*'))
                                <p class="mt-1 text-sm text-red-600">{{ $errors->first('features') ?: $errors->first('features.*') }}</p>
                            @endif

                            <div class="mt-3">
                                <button type="button" id="add-feature" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Tilføj feature</button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Opret</button>
                            <a href="{{ route('admin.subscription-plans.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Annuller</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('features-container');
    const addButton = document.getElementById('add-feature');

    function addFeatureInput(value = '') {
        const div = document.createElement('div');
        div.className = 'feature-input-group mb-2';
        div.innerHTML = `
            <input type="text" class="form-control" name="features[]" placeholder="Indtast feature" value="${value}">
            <button type="button" class="btn btn-outline-danger btn-sm remove-feature">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(div);
    }

    addButton.addEventListener('click', function() {
        addFeatureInput();
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-feature')) {
            const inputGroup = e.target.closest('.feature-input-group');
            if (container.children.length > 1) {
                inputGroup.remove();
            }
        }
    });

    // Add initial empty input if none exist
    if (container.children.length === 0) {
        addFeatureInput();
    }
});
</script>

</x-app-layout>
