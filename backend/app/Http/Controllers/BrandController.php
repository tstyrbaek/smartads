<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        if (!$user) {
            abort(404);
        }

        $activeCompanyId = (int) $request->session()->get('active_company_id');
        if (!$activeCompanyId) {
            $companyIds = $user->companies()->pluck('companies.id')->all();

            if (count($companyIds) === 1) {
                $activeCompanyId = (int) $companyIds[0];
                $request->session()->put('active_company_id', $activeCompanyId);
            } else {
                abort(403, 'No active company selected.');
            }
        }

        if (!$user->companies()->whereKey($activeCompanyId)->exists()) {
            abort(403);
        }

        $brand = Brand::query()->where('company_id', $activeCompanyId)->first();
        if (!$brand) {
            abort(404);
        }

        $this->authorize('update', $brand);

        return view('brands.edit', [
            'brand' => $brand,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(404);
        }

        $activeCompanyId = (int) $request->session()->get('active_company_id');
        if (!$activeCompanyId) {
            $companyIds = $user->companies()->pluck('companies.id')->all();

            if (count($companyIds) === 1) {
                $activeCompanyId = (int) $companyIds[0];
                $request->session()->put('active_company_id', $activeCompanyId);
            } else {
                abort(403, 'No active company selected.');
            }
        }

        if (!$user->companies()->whereKey($activeCompanyId)->exists()) {
            abort(403);
        }

        $brand = Brand::query()->where('company_id', $activeCompanyId)->first();
        if (!$brand) {
            abort(404);
        }

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

        return redirect()->route('brand.edit');
    }
}
