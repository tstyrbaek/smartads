<?php

namespace App\Http\Controllers\Api;

use App\Jobs\GenerateAdImageJob;
use App\Jobs\PostAdToFacebookPageJob;
use App\Models\Ad;
use App\Models\Brand;
use App\Models\Company;
use App\Models\IntegrationDefinition;
use App\Models\IntegrationInstance;
use App\Services\AdSizeService;
use App\Services\TokenUsageService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $ads = Ad::query()
            ->where('company_id', $companyId)
            ->with('integrationInstances')
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        return response()->json([
            'ads' => $ads->map(fn (Ad $ad) => $this->serializeAd($ad))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'target_url' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'image_width' => ['nullable', 'integer', 'min:50', 'max:4000'],
            'image_height' => ['nullable', 'integer', 'min:50', 'max:4000'],
            'debug' => ['nullable'],
            'images' => ['nullable'],
            'images.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $debugRequested = ($validated['debug'] ?? null) === true
            || ($validated['debug'] ?? null) === 1
            || ($validated['debug'] ?? null) === '1';

        $company = Company::query()->with('brand')->find($companyId);
        if (!$company) {
            abort(404);
        }

        $tokenService = app(TokenUsageService::class);
        if (!$tokenService->canGenerateAd($company, 1000)) {
            return response()->json([
                'error' => 'insufficient_tokens',
                'remaining_tokens' => $tokenService->getRemainingTokens($company),
                'required_tokens' => 1000,
            ], 403);
        }

        if (!$company->brand || !$company->brand->logo_path) {
            return response()->json(['error' => 'brand_logo_missing'], 400);
        }

        $imageWidth = isset($validated['image_width']) && is_numeric($validated['image_width']) ? (int) $validated['image_width'] : 800;
        $imageHeight = isset($validated['image_height']) && is_numeric($validated['image_height']) ? (int) $validated['image_height'] : 800;

        $sizeService = app(AdSizeService::class);
        if (!$sizeService->isAllowed($imageWidth, $imageHeight)) {
            return response()->json([
                'error' => 'invalid_ad_size',
                'allowed_sizes' => $sizeService->allowedSizes(),
            ], 422);
        }

        $id = (string) Str::ulid();
        $datePart = now()->format('ymd');
        $shortId = substr($id, -6);
        $companyPart = Str::slug((string) ($company->name ?? 'company'));
        if ($companyPart === '') {
            $companyPart = 'company';
        }
        $title = $companyPart . '-' . $datePart . '-' . $shortId;

        $imagePaths = [];
        $files = $request->file('images');
        if (is_array($files)) {
            $max = min(5, count($files));
            for ($i = 0; $i < $max; $i++) {
                $file = $files[$i] ?? null;
                if (!$file) {
                    continue;
                }

                $path = Storage::disk('local')->putFile('tmp/ad-input/' . $id, $file);
                if (is_string($path) && $path !== '') {
                    $imagePaths[] = $path;
                }
            }
        }

        $text = isset($validated['text']) ? trim((string) $validated['text']) : '';
        $targetUrl = isset($validated['target_url']) ? trim((string) $validated['target_url']) : '';
        $instructions = isset($validated['instructions']) ? trim((string) $validated['instructions']) : '';

        if ($targetUrl !== '' && !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'invalid_target_url'], 422);
        }

        if ($text === '' && ($instructions === '' || count($imagePaths) < 1)) {
            return response()->json([
                'error' => 'text_required',
                'message' => 'Text er påkrævet, medmindre du både har instrukser og mindst ét referencebillede.',
            ], 422);
        }

        $ad = Ad::query()->create([
            'id' => $id,
            'company_id' => $companyId,
            'user_id' => $user->id,
            'title' => $title,
            'text' => $text,
            'target_url' => $targetUrl !== '' ? $targetUrl : null,
            'instructions' => $instructions !== '' ? $instructions : null,
            'image_width' => $imageWidth,
            'image_height' => $imageHeight,
            'status' => 'generating',
            'input_image_paths' => $imagePaths,
        ]);

        dispatch(new GenerateAdImageJob($ad->id, $debugRequested));

        $payload = [
            'adId' => $ad->id,
            'status' => $ad->status,
        ];

        if ($debugRequested) {
            $payload['debug'] = $ad->debug;
        }

        return response()->json($payload);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $ad = Ad::query()->where('company_id', $companyId)->with('integrationInstances')->findOrFail($id);

        $downloadUrl = null;
        $previewUrl = null;
        if ($ad->status === 'success' && is_string($ad->local_file_path) && $ad->local_file_path !== '') {
            $downloadUrl = '/storage/' . ltrim($ad->local_file_path, '/');
            $previewUrl = '/storage/' . ltrim($ad->local_file_path, '/');
        }

        $debug = null;
        if ($request->query('debug') === '1') {
            $debug = is_array($ad->debug) ? $ad->debug : null;
        }

        return response()->json([
            'ad' => $this->serializeAd($ad),
            'downloadUrl' => $downloadUrl,
            'previewUrl' => $previewUrl,
            'debug' => $debug,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $ad = Ad::query()->where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'target_url' => ['nullable', 'string', 'max:2000'],
        ]);

        $targetUrl = isset($validated['target_url']) ? trim((string) $validated['target_url']) : '';
        if ($targetUrl !== '' && !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'invalid_target_url'], 422);
        }

        $ad->forceFill([
            'target_url' => $targetUrl !== '' ? $targetUrl : null,
        ])->save();

        $ad->load('integrationInstances');

        return response()->json([
            'ok' => true,
            'ad' => $this->serializeAd($ad),
        ]);
    }

    public function integrations(Request $request, string $id): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $ad = Ad::query()->where('company_id', $companyId)->with('integrationInstances')->findOrFail($id);

        return response()->json([
            'selected_instance_ids' => $ad->integrationInstances->pluck('id')->map(fn ($v) => (int) $v)->values()->all(),
        ]);
    }

    public function updateIntegrations(Request $request, string $id): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $ad = Ad::query()->where('company_id', $companyId)->with('integrationInstances')->findOrFail($id);

        $validated = $request->validate([
            'instance_ids' => ['nullable', 'array'],
            'instance_ids.*' => ['integer', 'exists:integration_instances,id'],
        ]);

        $instanceIds = collect($validated['instance_ids'] ?? [])->map(fn ($v) => (int) $v)->unique()->values()->all();

        if (!empty($instanceIds)) {
            $instances = IntegrationInstance::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $instanceIds)
                ->get();

            if ($instances->count() !== count($instanceIds)) {
                return response()->json(['error' => 'invalid_instance'], 422);
            }

            $definitionByKey = IntegrationDefinition::query()
                ->whereIn('key', $instances->pluck('integration_key')->unique()->values()->all())
                ->where('is_active', true)
                ->get()
                ->keyBy('key');

            foreach ($instances as $instance) {
                $expectedW = null;
                $expectedH = null;

                if ((string) $instance->integration_key === 'website_embed') {
                    $config = is_array($instance->config) ? $instance->config : [];
                    $expectedW = isset($config['ad_width']) && is_numeric($config['ad_width']) ? (int) $config['ad_width'] : null;
                    $expectedH = isset($config['ad_height']) && is_numeric($config['ad_height']) ? (int) $config['ad_height'] : null;
                } else {
                    $definition = $definitionByKey->get((string) $instance->integration_key);
                    if (!$definition) {
                        continue;
                    }

                    $caps = is_array($definition->capabilities) ? $definition->capabilities : [];
                    $expectedW = isset($caps['ad_width']) && is_numeric($caps['ad_width']) ? (int) $caps['ad_width'] : null;
                    $expectedH = isset($caps['ad_height']) && is_numeric($caps['ad_height']) ? (int) $caps['ad_height'] : null;

                    if ($expectedW && $expectedH) {
                        $sizeService = app(AdSizeService::class);
                        if (!$sizeService->isAllowed($expectedW, $expectedH)) {
                            return response()->json([
                                'error' => 'invalid_integration_ad_size',
                                'allowed_sizes' => $sizeService->allowedSizes(),
                            ], 422);
                        }
                    }
                }

                if (!$expectedW || !$expectedH) {
                    continue;
                }

                $actualW = is_numeric($ad->image_width) ? (int) $ad->image_width : null;
                $actualH = is_numeric($ad->image_height) ? (int) $ad->image_height : null;

                if (!$actualW || !$actualH || $actualW !== $expectedW || $actualH !== $expectedH) {
                    return response()->json([
                        'error' => 'invalid_ad_size',
                        'integration_instance_id' => (int) $instance->id,
                        'expected' => ['width' => $expectedW, 'height' => $expectedH],
                        'actual' => ['width' => $actualW, 'height' => $actualH],
                    ], 422);
                }
            }
        }

        $now = now();

        $existingById = $ad->integrationInstances->keyBy('id');

        $syncData = [];
        foreach ($instanceIds as $instanceId) {
            $existingPivotPublishedAt = $existingById->get($instanceId)?->pivot?->published_at;
            $syncData[$instanceId] = [
                'status' => 'selected',
                'published_at' => $existingPivotPublishedAt ?? $now,
            ];
        }

        $ad->integrationInstances()->sync($syncData);

        foreach ($instanceIds as $instanceId) {
            $instance = $instances->firstWhere('id', $instanceId);
            if (!$instance) {
                continue;
            }
            if ((string) $instance->integration_key !== 'facebook_page') {
                continue;
            }

            $existingPivot = $existingById->get($instanceId)?->pivot;
            $alreadyPublished = false;
            if ($existingPivot) {
                $meta = is_array($existingPivot->meta) ? $existingPivot->meta : null;
                $alreadyPublished = (string) ($existingPivot->status ?? '') === 'published'
                    || (is_array($meta) && isset($meta['facebook_post_id']) && is_string($meta['facebook_post_id']) && trim($meta['facebook_post_id']) !== '');
            }

            if ($alreadyPublished) {
                continue;
            }

            PostAdToFacebookPageJob::dispatch($ad->id, (int) $instanceId);
        }

        $ad->load('integrationInstances');

        return response()->json([
            'ok' => true,
            'ad' => $this->serializeAd($ad),
        ]);
    }

    public function download(Request $request, string $id): BinaryFileResponse|JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $ad = Ad::query()->where('company_id', $companyId)->findOrFail($id);
        $local = (string) ($ad->local_file_path ?? '');
        if ($local === '') {
            return response()->json(['error' => 'not_ready'], 409);
        }

        $absolute = Storage::disk('public')->path($local);
        $ext = strtolower((string) pathinfo($absolute, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return response()->download($absolute, $ad->id . '.' . pathinfo($absolute, PATHINFO_EXTENSION), [
            'content-type' => $mime,
        ]);
    }

    public function image(Request $request, string $id): StreamedResponse|JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $ad = Ad::query()->where('company_id', $companyId)->findOrFail($id);
        $local = (string) ($ad->local_file_path ?? '');
        if ($local === '') {
            return response()->json(['error' => 'not_ready'], 409);
        }

        if (!Storage::disk('public')->exists($local)) {
            return response()->json(['error' => 'file_not_found'], 404);
        }

        $ext = strtolower((string) pathinfo($local, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return response()->stream(function () use ($local) {
            echo Storage::disk('public')->get($local);
        }, 200, [
            'content-type' => $mime,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $companyId = (int) $request->attributes->get('active_company_id');

        $ad = Ad::query()->where('company_id', $companyId)->findOrFail($id);

        if (is_string($ad->local_file_path) && $ad->local_file_path !== '') {
            Storage::disk('public')->delete($ad->local_file_path);
        }

        Storage::disk('local')->deleteDirectory('tmp/ad-input/' . $ad->id);

        $ad->delete();

        return response()->json(['ok' => true]);
    }

    private function serializeAd(Ad $ad): array
    {
        $ad->loadMissing('integrationInstances');

        $filePath = null;
        if (is_string($ad->local_file_path) && $ad->local_file_path !== '') {
            $filePath = '/storage/' . ltrim($ad->local_file_path, '/');
        }

        return [
            'id' => $ad->id,
            'title' => $ad->title,
            'text' => $ad->text,
            'targetUrl' => is_string($ad->target_url) ? $ad->target_url : null,
            'instructions' => $ad->instructions,
            'prompt' => is_string($ad->prompt) ? $ad->prompt : null,
            'promptVersion' => is_string($ad->prompt_version) ? $ad->prompt_version : null,
            'status' => $ad->status,
            'nanobananaTaskId' => null,
            'localFilePath' => $filePath,
            'imageWidth' => is_numeric($ad->image_width) ? (int) $ad->image_width : 800,
            'imageHeight' => is_numeric($ad->image_height) ? (int) $ad->image_height : 800,
            'promptTokens' => is_numeric($ad->prompt_tokens) ? (int) $ad->prompt_tokens : null,
            'outputTokens' => is_numeric($ad->output_tokens) ? (int) $ad->output_tokens : null,
            'totalTokens' => is_numeric($ad->total_tokens) ? (int) $ad->total_tokens : null,
            'inputImagePaths' => is_array($ad->input_image_paths) ? $ad->input_image_paths : null,
            'brandSnapshot' => is_array($ad->brand_snapshot) ? $ad->brand_snapshot : null,
            'debug' => is_array($ad->debug) ? $ad->debug : null,
            'integrationInstances' => $ad->integrationInstances
                ->map(fn (IntegrationInstance $instance) => [
                    'id' => (int) $instance->id,
                    'integration_key' => (string) $instance->integration_key,
                    'name' => (string) $instance->name,
                    'is_active' => (bool) $instance->is_active,
                    'published_at' => (function () use ($instance) {
                        $publishedAt = $instance->pivot?->published_at;
                        if ($publishedAt instanceof \DateTimeInterface) {
                            return Carbon::instance($publishedAt)->toISOString();
                        }
                        if (is_string($publishedAt) && $publishedAt !== '') {
                            try {
                                return Carbon::parse($publishedAt)->toISOString();
                            } catch (\Throwable) {
                                return null;
                            }
                        }
                        return null;
                    })(),
                ])
                ->values(),
            'error' => $ad->error,
            'createdAt' => $ad->created_at?->toISOString(),
            'updatedAt' => $ad->updated_at?->toISOString(),
        ];
    }
}
