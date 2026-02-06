<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Company
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('company.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="company_name">Company name</label>
                            <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $company->name) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('company_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="website_url">Website URL</label>
                            <input id="website_url" name="website_url" type="url" value="{{ old('website_url', $company->website_url) }}" placeholder="https://example.com" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            @error('website_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="company_description">Company description</label>
                            <textarea id="company_description" name="company_description" rows="5" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('company_description', $company->company_description) }}</textarea>
                            @error('company_description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700" for="target_audience_description">Target audience description</label>
                            <textarea id="target_audience_description" name="target_audience_description" rows="5" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('target_audience_description', $company->target_audience_description) }}</textarea>
                            @error('target_audience_description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="border border-gray-200 rounded-md p-4">
                            <div class="font-medium text-gray-900">Subscription</div>

                            @php
                                $currentSubscription = $company->getCurrentSubscription();
                            @endphp

                            @if ($currentSubscription)
                                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <dt class="text-xs text-gray-500">Pakke</dt>
                                        <dd class="mt-1 text-gray-900 font-medium">{{ $currentSubscription->plan->name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500">Status</dt>
                                        <dd class="mt-1">
                                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktiv</span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500">Periode</dt>
                                        <dd class="mt-1 text-gray-900">
                                            {{ $currentSubscription->starts_at->format('d/m/Y') }}
                                            @if($currentSubscription->ends_at)
                                                - {{ $currentSubscription->ends_at->format('d/m/Y') }}
                                            @else
                                                - Ubegrænset
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            @else
                                @php
                                    $subscription = $company->subscription;
                                @endphp

                                @if ($subscription)
                                    <div class="mt-4 text-sm">
                                        <div class="font-medium">{{ $subscription->plan?->name ?? '-' }}</div>
                                        <div class="mt-2">
                                            @if(!$subscription->is_active)
                                                <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20">Inaktiv</span>
                                            @elseif($subscription->isExpired())
                                                <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Udløbet</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktiv</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-900">Ingen aktivt abonnement fundet</div>
                                @endif
                            @endif
                        </div>

                        <details class="border border-gray-200 rounded-md p-4">
                            <summary class="cursor-pointer font-medium text-gray-900">Brand information</summary>

                            <div class="mt-4 space-y-6">
                                <div>
                                    <label class="block font-medium text-sm text-gray-700" for="brand_color_1">Brand color 1</label>
                                    <div class="mt-1 flex items-center gap-3">
                                        <input id="brand_color_1_picker" type="color" value="{{ old('brand_color_1', $company->brand?->color_1 ?? '#000000') }}" class="h-10 w-12 border border-gray-300 rounded-md" oninput="document.getElementById('brand_color_1').value=this.value" />
                                        <input id="brand_color_1" name="brand_color_1" type="text" value="{{ old('brand_color_1', $company->brand?->color_1) }}" placeholder="#112233" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)){document.getElementById('brand_color_1_picker').value=this.value}" />
                                    </div>
                                    @error('brand_color_1')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block font-medium text-sm text-gray-700" for="brand_color_2">Brand color 2</label>
                                    <div class="mt-1 flex items-center gap-3">
                                        <input id="brand_color_2_picker" type="color" value="{{ old('brand_color_2', $company->brand?->color_2 ?? '#000000') }}" class="h-10 w-12 border border-gray-300 rounded-md" oninput="document.getElementById('brand_color_2').value=this.value" />
                                        <input id="brand_color_2" name="brand_color_2" type="text" value="{{ old('brand_color_2', $company->brand?->color_2) }}" placeholder="#112233" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)){document.getElementById('brand_color_2_picker').value=this.value}" />
                                    </div>
                                    @error('brand_color_2')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block font-medium text-sm text-gray-700" for="brand_color_3">Brand color 3</label>
                                    <div class="mt-1 flex items-center gap-3">
                                        <input id="brand_color_3_picker" type="color" value="{{ old('brand_color_3', $company->brand?->color_3 ?? '#000000') }}" class="h-10 w-12 border border-gray-300 rounded-md" oninput="document.getElementById('brand_color_3').value=this.value" />
                                        <input id="brand_color_3" name="brand_color_3" type="text" value="{{ old('brand_color_3', $company->brand?->color_3) }}" placeholder="#112233" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)){document.getElementById('brand_color_3_picker').value=this.value}" />
                                    </div>
                                    @error('brand_color_3')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block font-medium text-sm text-gray-700" for="brand_color_4">Brand color 4</label>
                                    <div class="mt-1 flex items-center gap-3">
                                        <input id="brand_color_4_picker" type="color" value="{{ old('brand_color_4', $company->brand?->color_4 ?? '#000000') }}" class="h-10 w-12 border border-gray-300 rounded-md" oninput="document.getElementById('brand_color_4').value=this.value" />
                                        <input id="brand_color_4" name="brand_color_4" type="text" value="{{ old('brand_color_4', $company->brand?->color_4) }}" placeholder="#112233" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)){document.getElementById('brand_color_4_picker').value=this.value}" />
                                    </div>
                                    @error('brand_color_4')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <div class="text-sm text-gray-500 mb-2">Current logo</div>
                                    @if ($company->brand?->logo_path)
                                        <a class="text-indigo-600 hover:text-indigo-900" href="{{ asset('storage/' . $company->brand->logo_path) }}" target="_blank">View</a>
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
                            </div>
                        </details>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
