<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        return view('admin.companies.index', [
            'companies' => Company::query()
                ->with(['subscription.plan'])
                ->orderBy('id', 'desc')
                ->paginate(50),
        ]);
    }

    public function create(): View
    {
        return view('admin.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'company_description' => ['nullable', 'string'],
            'target_audience_description' => ['nullable', 'string'],
            'member_user_ids' => ['nullable', 'array'],
            'member_user_ids.*' => ['integer', 'exists:users,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'brand_color_1' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_color_2' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_color_3' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_color_4' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $company = Company::query()->create([
            'name' => $validated['company_name'],
            'website_url' => $validated['website_url'] ?? null,
            'company_description' => $validated['company_description'] ?? null,
            'target_audience_description' => $validated['target_audience_description'] ?? null,
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = Storage::disk('public')->putFile('brand-logos', $request->file('logo'));
        }

        Brand::query()->create([
            'company_id' => $company->id,
            'name' => $company->name,
            'logo_path' => $logoPath,
            'color_1' => $validated['brand_color_1'] ?? null,
            'color_2' => $validated['brand_color_2'] ?? null,
            'color_3' => $validated['brand_color_3'] ?? null,
            'color_4' => $validated['brand_color_4'] ?? null,
        ]);

        $adminUserIds = User::query()->where('role', 'admin')->pluck('id')->all();
        $memberUserIds = array_values(array_unique(array_merge($validated['member_user_ids'] ?? [], $adminUserIds)));
        $company->users()->sync($memberUserIds);

        return redirect()->route('admin.companies.index');
    }

    public function edit(Company $company): View
    {
        return view('admin.companies.edit', [
            'company' => $company->loadMissing('brand', 'users', 'subscription.plan', 'subscriptions.plan', 'integrationInstances'),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'company_description' => ['nullable', 'string'],
            'target_audience_description' => ['nullable', 'string'],
            'member_user_ids' => ['nullable', 'array'],
            'member_user_ids.*' => ['integer', 'exists:users,id'],
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

        $adminUserIds = User::query()->where('role', 'admin')->pluck('id')->all();
        $memberUserIds = array_values(array_unique(array_merge($validated['member_user_ids'] ?? [], $adminUserIds)));
        $company->users()->sync($memberUserIds);

        if ($request->hasFile('logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }

            $brand->forceFill([
                'logo_path' => Storage::disk('public')->putFile('brand-logos', $request->file('logo')),
            ])->save();
        }

        return redirect()->route('admin.companies.index');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $brand = $company->brand;
        if ($brand && $brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $company->delete();

        return redirect()->route('admin.companies.index');
    }
}
