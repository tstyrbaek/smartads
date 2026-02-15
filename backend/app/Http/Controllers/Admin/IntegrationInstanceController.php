<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\IntegrationDefinition;
use App\Models\IntegrationInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IntegrationInstanceController extends Controller
{
    public function create(Company $company): View
    {
        return view('admin.integrations.instances.create', [
            'company' => $company,
            'definitions' => IntegrationDefinition::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'integration_key' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
            'config.*' => ['nullable'],
        ]);

        $exists = IntegrationDefinition::query()->where('key', $validated['integration_key'])->where('is_active', true)->exists();
        if (!$exists) {
            return redirect()->back()->withInput()->with('error', 'Ukendt integration');
        }

        $config = $validated['config'] ?? [];
        if (($validated['integration_key'] ?? '') === 'website_embed') {
            $token = (string) ($config['embed_token'] ?? '');
            if ($token === '') {
                $config['embed_token'] = Str::random(48);
            }
        }

        $instance = IntegrationInstance::query()->create([
            'company_id' => $company->id,
            'integration_key' => $validated['integration_key'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
            'config' => empty($config) ? null : $config,
            'credentials' => null,
        ]);

        return redirect()->route('admin.companies.integrations.edit', [$company, $instance])->with('success', 'Integration oprettet');
    }

    public function edit(Company $company, IntegrationInstance $instance): View
    {
        if ((int) $instance->company_id !== (int) $company->id) {
            abort(404);
        }

        $instance->load(['ads']);

        return view('admin.integrations.instances.edit', [
            'company' => $company,
            'instance' => $instance,
            'definitions' => IntegrationDefinition::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Company $company, IntegrationInstance $instance): RedirectResponse
    {
        if ((int) $instance->company_id !== (int) $company->id) {
            abort(404);
        }

        $validated = $request->validate([
            'integration_key' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
            'config.*' => ['nullable'],
        ]);

        $exists = IntegrationDefinition::query()->where('key', $validated['integration_key'])->where('is_active', true)->exists();
        if (!$exists) {
            return redirect()->back()->withInput()->with('error', 'Ukendt integration');
        }

        $config = $validated['config'] ?? [];
        if (($validated['integration_key'] ?? '') === 'website_embed') {
            $currentToken = (string) ($instance->config['embed_token'] ?? '');
            $incomingToken = (string) ($config['embed_token'] ?? '');
            if ($incomingToken === '' && $currentToken !== '') {
                $config['embed_token'] = $currentToken;
            }
        }

        $instance->forceFill([
            'integration_key' => $validated['integration_key'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
            'config' => empty($config) ? null : $config,
        ])->save();

        return redirect()->route('admin.companies.edit', $company)->with('success', 'Integration opdateret');
    }

    public function destroy(Company $company, IntegrationInstance $instance): RedirectResponse
    {
        if ((int) $instance->company_id !== (int) $company->id) {
            abort(404);
        }

        $instance->delete();

        return redirect()->route('admin.companies.edit', $company)->with('success', 'Integration slettet');
    }
}
