<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationDefinition;
use App\Models\IntegrationInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IntegrationDefinitionController extends Controller
{
    public function index(): View
    {
        return view('admin.integration-definitions.index', [
            'definitions' => IntegrationDefinition::query()->orderBy('name')->paginate(50),
        ]);
    }

    public function create(): View
    {
        return view('admin.integration-definitions.create', [
            'definition' => new IntegrationDefinition(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDefinition($request);
        if (array_key_exists('capabilities_error', $validated)) {
            return redirect()->back()->withInput()->withErrors(['capabilities' => $validated['capabilities_error']]);
        }

        $definition = IntegrationDefinition::query()->create($validated);

        return redirect()->route('admin.integration-definitions.edit', $definition)->with('success', 'Integrationstype oprettet');
    }

    public function edit(IntegrationDefinition $definition): View
    {
        $inUseCount = IntegrationInstance::query()
            ->where('integration_key', $definition->key)
            ->count();

        return view('admin.integration-definitions.edit', [
            'definition' => $definition,
            'inUseCount' => $inUseCount,
        ]);
    }

    public function update(Request $request, IntegrationDefinition $definition): RedirectResponse
    {
        $inUseCount = IntegrationInstance::query()
            ->where('integration_key', $definition->key)
            ->count();

        if ($inUseCount > 0 && $request->filled('key') && (string) $request->input('key') !== (string) $definition->key) {
            return redirect()->back()->withInput()->with('error', 'Key kan ikke ændres, når integrationstypen er i brug.');
        }

        $validated = $this->validateDefinition($request, $definition);
        if (array_key_exists('capabilities_error', $validated)) {
            return redirect()->back()->withInput()->withErrors(['capabilities' => $validated['capabilities_error']]);
        }

        $definition->forceFill($validated)->save();

        return redirect()->route('admin.integration-definitions.edit', $definition)->with('success', 'Integrationstype opdateret');
    }

    public function destroy(IntegrationDefinition $definition): RedirectResponse
    {
        $inUseCount = IntegrationInstance::query()
            ->where('integration_key', $definition->key)
            ->count();

        if ($inUseCount > 0) {
            return redirect()->route('admin.integration-definitions.index')->with('error', 'Integrationstypen kan ikke slettes, fordi den er i brug.');
        }

        $definition->delete();

        return redirect()->route('admin.integration-definitions.index')->with('success', 'Integrationstype slettet');
    }

    private function validateDefinition(Request $request, ?IntegrationDefinition $definition = null): array
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', Rule::unique('integration_definitions', 'key')->ignore($definition?->id)],
            'type' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capabilities' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $capabilities = null;
        $capabilitiesRaw = trim((string) ($validated['capabilities'] ?? ''));
        if ($capabilitiesRaw !== '') {
            $decoded = json_decode($capabilitiesRaw, true);
            if (!is_array($decoded)) {
                return ['capabilities_error' => 'Capabilities skal være gyldig JSON (array).'];
            }
            $capabilities = $decoded;
        }

        return [
            'key' => $validated['key'],
            'type' => $validated['type'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'capabilities' => $capabilities,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
