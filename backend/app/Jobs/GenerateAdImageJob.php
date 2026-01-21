<?php

namespace App\Jobs;

use App\Models\Ad;
use App\Models\Company;
use App\Services\Gemini\GeminiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateAdImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly string $adId,
        public readonly bool $debugRequested = false,
    ) {
    }

    public function handle(): void
    {
        $ad = Ad::query()->find($this->adId);
        if (!$ad) {
            return;
        }

        $company = Company::query()->with('brand')->find($ad->company_id);
        if (!$company) {
            $ad->forceFill([
                'status' => 'failed',
                'error' => 'company_not_found',
            ])->save();
            return;
        }

        $brand = $company->brand;
        $logoPath = $brand?->logo_path;
        if (!$logoPath) {
            $ad->forceFill([
                'status' => 'failed',
                'error' => 'brand_logo_missing',
            ])->save();
            return;
        }

        $apiKey = (string) config('services.gemini.api_key');
        if ($apiKey === '') {
            $ad->forceFill([
                'status' => 'failed',
                'error' => 'gemini_api_key_missing',
            ])->save();
            return;
        }

        $colors = array_values(array_filter([
            $brand?->color_1,
            $brand?->color_2,
            $brand?->color_3,
            $brand?->color_4,
        ], fn ($c) => is_string($c) && $c !== ''));

        $colorsText = $colors !== [] ? implode(', ', $colors) : '';

        $prompt = "Create a clean, modern square 1:1 web advertisement image.\n";
        if ($colorsText !== '') {
            $prompt .= "Use the brand colors: {$colorsText}.\n";
        }
        $prompt .= "Company description: {$company->company_description}.\n";
        $prompt .= "Target audience: {$company->target_audience_description}.\n";
        $prompt .= "Logo: Use the provided reference logo image (do not invent a new logo). Include EXACTLY ONE logo in the design. Do not duplicate the logo elsewhere.\n";
        $prompt .= "The ad text must be clearly readable and spelled correctly, and MUST appear EXACTLY as written (do not change wording, spelling):\n\"{$ad->text}\"\n";
        $prompt .= "Minimal layout, high contrast, professional typography, safe margins.";

        $referenceImages = [];

        $logoExt = strtolower((string) pathinfo($logoPath, PATHINFO_EXTENSION));
        $logoMime = match ($logoExt) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
        $logoBin = Storage::disk('public')->get($logoPath);
        $referenceImages[] = [
            'mimeType' => $logoMime,
            'data' => base64_encode($logoBin),
        ];

        $inputImagePaths = is_array($ad->input_image_paths) ? $ad->input_image_paths : [];
        $productCount = 0;
        foreach ($inputImagePaths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }
            if (!Storage::disk('local')->exists($path)) {
                continue;
            }

            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };
            if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
                continue;
            }
            $bin = Storage::disk('local')->get($path);
            if ($bin === '') {
                continue;
            }
            $referenceImages[] = [
                'mimeType' => $mime,
                'data' => base64_encode($bin),
            ];
            $productCount++;
        }

        if ($productCount > 0) {
            $prompt .= "\nProduct images requirement: You MUST include ALL provided product reference images ({$productCount}) in the final ad.\n";
            $prompt .= "Layout requirement: Show all product images clearly (for example as a collage/grid or multiple tiles). Do not omit any of them.\n";
        }

        $model = (string) config('services.gemini.model', 'gemini-3-pro-image-preview');
        $aspectRatio = (string) config('services.gemini.aspect_ratio', '1:1');
        $imageSize = (string) config('services.gemini.image_size', '1K');

        try {
            $client = new GeminiClient($apiKey);
            $imagePart = $client->generateImage($model, $prompt, $referenceImages, $aspectRatio, $imageSize);

            $mimeType = (string) ($imagePart['mimeType'] ?? 'application/octet-stream');
            $data = (string) ($imagePart['data'] ?? '');

            $generatedBin = base64_decode($data, true);
            if (!is_string($generatedBin) || $generatedBin === '') {
                throw new \RuntimeException('gemini_image_decode_failed');
            }

            $ext = match ($mimeType) {
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                default => 'bin',
            };

            $relative = 'generated/ads/' . $ad->id . '.' . $ext;
            Storage::disk('public')->put($relative, $generatedBin);

            $debug = null;
            if ($this->debugRequested) {
                $debug = [
                    'geminiRequest' => [
                        'model' => $model,
                        'prompt' => $prompt,
                        'aspectRatio' => $aspectRatio,
                        'imageSize' => $imageSize,
                        'referenceImagesCount' => count($referenceImages),
                        'productImagesCount' => $productCount,
                        'logo' => [
                            'mimeType' => $logoMime,
                            'path' => $logoPath,
                            'bytes' => strlen($logoBin),
                        ],
                    ],
                ];
            }

            $ad->forceFill([
                'status' => 'success',
                'local_file_path' => $relative,
                'error' => null,
                'debug' => $debug,
            ])->save();
        } catch (\Throwable $e) {
            $ad->forceFill([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ])->save();
        } finally {
            Storage::disk('local')->deleteDirectory('tmp/ad-input/' . $ad->id);
        }
    }
}
