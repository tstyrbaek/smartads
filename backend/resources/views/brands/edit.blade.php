<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Brand
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('brand.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="brand_name">Brand name</label>
                            <input id="brand_name" name="brand_name" type="text" value="{{ old('brand_name', $brand->name) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('brand_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="text-sm text-gray-500 mb-2">Current logo</div>
                            @if ($brand->logo_path)
                                <a class="text-indigo-600 hover:text-indigo-900" href="{{ asset('storage/' . $brand->logo_path) }}" target="_blank">View</a>
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="logo">Replace logo (optional)</label>
                            <input id="logo" name="logo" type="file" accept="image/*" class="mt-1 block w-full" />
                            @error('logo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
