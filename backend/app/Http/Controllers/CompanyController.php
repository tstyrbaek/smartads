<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
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

        $company = Company::query()->with('brand')->find($activeCompanyId);
        if (!$company) {
            abort(404);
        }

        return view('companies.edit', [
            'company' => $company,
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

        $company = Company::query()->with('brand')->find($activeCompanyId);
        if (!$company) {
            abort(404);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'company_description' => ['nullable', 'string'],
            'target_audience_description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'brand_color_1' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_color_2' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_color_3' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_color_4' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $company->forceFill([
            'name' => $validated['company_name'],
            'website_url' => $validated['website_url'] ?? null,
            'company_description' => $validated['company_description'] ?? null,
            'target_audience_description' => $validated['target_audience_description'] ?? null,
        ])->save();

        $brand = $company->brand;
        if (!$brand) {
            $brand = Brand::query()->create([
                'company_id' => $company->id,
                'name' => $company->name,
                'logo_path' => null,
                'color_1' => null,
                'color_2' => null,
                'color_3' => null,
                'color_4' => null,
            ]);
        }

        $brand->forceFill([
            'color_1' => $validated['brand_color_1'] ?? null,
            'color_2' => $validated['brand_color_2'] ?? null,
            'color_3' => $validated['brand_color_3'] ?? null,
            'color_4' => $validated['brand_color_4'] ?? null,
        ])->save();

        if ($request->hasFile('logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }

            $brand->forceFill([
                'logo_path' => Storage::disk('public')->putFile('brand-logos', $request->file('logo')),
            ])->save();
        }

        return redirect()->route('company.edit');
    }
}
