<?php

namespace App\Http\Controllers\Api;

use App\Jobs\GenerateAdImageJob;
use App\Models\Ad;
use App\Models\Company;
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

        $validated = $request->validate([
            'text' => ['required', 'string'],
            'instructions' => ['nullable', 'string', 'max:2000'],
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

        if (!$company->brand || !$company->brand->logo_path) {
            return response()->json(['error' => 'brand_logo_missing'], 400);
        }

        $id = (string) Str::ulid();

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

        $ad = Ad::query()->create([
            'id' => $id,
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'text' => (string) $validated['text'],
            'instructions' => isset($validated['instructions']) ? (string) $validated['instructions'] : null,
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

        $ad = Ad::query()->where('company_id', $companyId)->findOrFail($id);

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
        $filePath = null;
        if (is_string($ad->local_file_path) && $ad->local_file_path !== '') {
            $filePath = '/storage/' . ltrim($ad->local_file_path, '/');
        }

        return [
            'id' => $ad->id,
            'text' => $ad->text,
            'instructions' => $ad->instructions,
            'status' => $ad->status,
            'nanobananaTaskId' => null,
            'localFilePath' => $filePath,
            'error' => $ad->error,
            'updatedAt' => $ad->updated_at?->toISOString(),
        ];
    }
}
