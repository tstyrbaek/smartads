<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function show(Request $request)
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $company = Company::query()->with('brand')->find($companyId);
        if (!$company) {
            abort(404);
        }

        $brand = $company->brand;

        return response()->json([
            'companyName' => $company->name,
            'websiteUrl' => $company->website_url,
            'companyDescription' => $company->company_description,
            'audienceDescription' => $company->target_audience_description,
            'primaryColor1' => $brand?->color_1,
            'primaryColor2' => $brand?->color_2,
            'primaryColor3' => $brand?->color_3,
            'primaryColor4' => $brand?->color_4,
            'logoPath' => $brand?->logo_path ? '/storage/' . $brand->logo_path : null,
            'updatedAt' => $brand?->updated_at?->toISOString(),
        ]);
    }

    public function store(Request $request)
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $validated = $request->validate([
            'companyName' => ['required', 'string', 'max:255'],
            'websiteUrl' => ['nullable', 'url', 'max:2048'],
            'companyDescription' => ['nullable', 'string'],
            'audienceDescription' => ['nullable', 'string'],
            'primaryColor1' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primaryColor2' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primaryColor3' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primaryColor4' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $company = Company::query()->find($companyId);
        if (!$company) {
            abort(404);
        }

        $company->forceFill([
            'name' => $validated['companyName'],
            'website_url' => $validated['websiteUrl'] ?? null,
            'company_description' => $validated['companyDescription'] ?? null,
            'target_audience_description' => $validated['audienceDescription'] ?? null,
        ])->save();

        $brand = Brand::query()->firstOrCreate(
            ['company_id' => $company->id],
            ['name' => $company->name]
        );

        $brand->forceFill([
            'color_1' => $validated['primaryColor1'],
            'color_2' => $validated['primaryColor2'],
            'color_3' => $validated['primaryColor3'] ?? null,
            'color_4' => $validated['primaryColor4'] ?? null,
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

        return response()->json([
            'companyName' => $company->name,
            'websiteUrl' => $company->website_url,
            'companyDescription' => $company->company_description,
            'audienceDescription' => $company->target_audience_description,
            'primaryColor1' => $brand->color_1,
            'primaryColor2' => $brand->color_2,
            'primaryColor3' => $brand->color_3,
            'primaryColor4' => $brand->color_4,
            'logoPath' => $brand->logo_path ? '/storage/' . $brand->logo_path : null,
            'updatedAt' => $brand->updated_at?->toISOString(),
        ]);
    }
}
