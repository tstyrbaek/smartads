<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationDefinition;
use App\Models\IntegrationInstance;
use App\Services\AdSizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    public function definitions(): array
    {
        return [
            'definitions' => IntegrationDefinition::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['key', 'type', 'name', 'description', 'capabilities', 'is_active'])
                ->toArray(),
        ];
    }

    public function instances(Request $request): array
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        return [
            'instances' => IntegrationInstance::query()
                ->where('company_id', $companyId)
                ->orderByDesc('id')
                ->get(['id', 'company_id', 'integration_key', 'name', 'is_active', 'config', 'created_at', 'updated_at'])
                ->toArray(),
        ];
    }

    public function store(Request $request): array|JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $validated = $request->validate([
            'integration_key' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['nullable', 'array'],
            'config.*' => ['nullable'],
        ]);

        $exists = IntegrationDefinition::query()->where('key', $validated['integration_key'])->where('is_active', true)->exists();
        if (!$exists) {
            return response()->json(['error' => 'unknown_integration'], 422);
        }

        $config = $validated['config'] ?? [];
        if (($validated['integration_key'] ?? '') === 'website_embed') {
            $token = (string) ($config['embed_token'] ?? '');
            if ($token === '') {
                $config['embed_token'] = Str::random(48);
            }

            $w = isset($config['ad_width']) && is_numeric($config['ad_width']) ? (int) $config['ad_width'] : null;
            $h = isset($config['ad_height']) && is_numeric($config['ad_height']) ? (int) $config['ad_height'] : null;
            if (($w && !$h) || (!$w && $h)) {
                return response()->json(['error' => 'invalid_ad_size'], 422);
            }
            if ($w && $h) {
                $sizeService = app(AdSizeService::class);
                if (!$sizeService->isAllowed($w, $h)) {
                    return response()->json(['error' => 'invalid_ad_size', 'allowed_sizes' => $sizeService->allowedSizes()], 422);
                }
            }
        }

        $instance = IntegrationInstance::query()->create([
            'company_id' => $companyId,
            'integration_key' => $validated['integration_key'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
            'config' => empty($config) ? null : $config,
            'credentials' => null,
        ]);

        return [
            'instance' => $instance->only(['id', 'company_id', 'integration_key', 'name', 'is_active', 'config', 'created_at', 'updated_at']),
        ];
    }

    public function update(Request $request, IntegrationInstance $instance): array|JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');
        if ((int) $instance->company_id !== $companyId) {
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
            return response()->json(['error' => 'unknown_integration'], 422);
        }

        $config = $validated['config'] ?? [];
        if (($validated['integration_key'] ?? '') === 'website_embed') {
            $currentToken = (string) ($instance->config['embed_token'] ?? '');
            $incomingToken = (string) ($config['embed_token'] ?? '');
            if ($incomingToken === '' && $currentToken !== '') {
                $config['embed_token'] = $currentToken;
            }

            $w = isset($config['ad_width']) && is_numeric($config['ad_width']) ? (int) $config['ad_width'] : null;
            $h = isset($config['ad_height']) && is_numeric($config['ad_height']) ? (int) $config['ad_height'] : null;
            if (($w && !$h) || (!$w && $h)) {
                return response()->json(['error' => 'invalid_ad_size'], 422);
            }
            if ($w && $h) {
                $sizeService = app(AdSizeService::class);
                if (!$sizeService->isAllowed($w, $h)) {
                    return response()->json(['error' => 'invalid_ad_size', 'allowed_sizes' => $sizeService->allowedSizes()], 422);
                }
            }
        }

        $instance->forceFill([
            'integration_key' => $validated['integration_key'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
            'config' => empty($config) ? null : $config,
        ])->save();

        return [
            'instance' => $instance->only(['id', 'company_id', 'integration_key', 'name', 'is_active', 'config', 'created_at', 'updated_at']),
        ];
    }

    public function destroy(Request $request, IntegrationInstance $instance): array
    {
        $companyId = (int) $request->attributes->get('active_company_id');
        if ((int) $instance->company_id !== $companyId) {
            abort(404);
        }

        $instance->delete();

        return ['ok' => true];
    }
}
