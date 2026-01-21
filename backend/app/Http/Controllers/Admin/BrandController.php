<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Brand::class);

        return view('admin.brands.index', [
            'brands' => Brand::query()->with('company')->orderBy('id', 'desc')->paginate(50),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Brand::class);

        return view('admin.brands.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $company = Company::query()->create([
            'name' => $validated['company_name'],
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = Storage::disk('public')->putFile('brand-logos', $request->file('logo'));
        }

        Brand::query()->create([
            'company_id' => $company->id,
            'name' => $validated['brand_name'],
            'logo_path' => $logoPath,
        ]);

        return redirect()->route('admin.brands.index');
    }

    public function edit(Brand $brand): View
    {
        $this->authorize('update', $brand);

        return view('admin.brands.edit', [
            'brand' => $brand->loadMissing('company'),
        ]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $this->authorize('update', $brand);

        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $brand->forceFill([
            'name' => $validated['brand_name'],
        ]);

        if ($request->hasFile('logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }

            $brand->forceFill([
                'logo_path' => Storage::disk('public')->putFile('brand-logos', $request->file('logo')),
            ]);
        }

        $brand->save();

        return redirect()->route('admin.brands.index');
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        if ($brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $brand->delete();

        return redirect()->route('admin.brands.index');
    }
}
