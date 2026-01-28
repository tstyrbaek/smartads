<?php

namespace App\Jobs;

use App\Events\AdUpdated;
use App\Models\Ad;
use App\Models\Company;
use App\Services\Gemini\GeminiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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

        $brandSnapshot = [
            'colors' => $colors,
            'logo_path' => $logoPath,
            'fonts' => $brand?->fonts,
            'slogan' => $brand?->slogan,
            'visual_guidelines' => $brand?->visual_guidelines,
            'company_description' => $company->company_description,
            'target_audience_description' => $company->target_audience_description,
        ];

        $instructions = is_string($ad->instructions) ? trim($ad->instructions) : '';

        $prompt = "Create a clean, modern square 1:1 web advertisement image.\n";
        if ($colorsText !== '') {
            $prompt .= "Use the brand colors: {$colorsText}.\n";
        }
        if (is_string($brand?->fonts) && trim($brand->fonts) !== '') {
            $prompt .= "Brand fonts: " . trim($brand->fonts) . ".\n";
        }
        if (is_string($brand?->slogan) && trim($brand->slogan) !== '') {
            $prompt .= "Brand slogan/tagline: \"" . trim($brand->slogan) . "\".\n";
        }
        if (is_string($brand?->visual_guidelines) && trim($brand->visual_guidelines) !== '') {
            $prompt .= "Visual guidelines: " . trim($brand->visual_guidelines) . "\n";
        }
        $prompt .= "Company description: {$company->company_description}.\n";
        $prompt .= "Target audience: {$company->target_audience_description}.\n";
        $prompt .= "Logo: Use the provided reference logo image (do not invent a new logo). Include EXACTLY ONE logo in the design. Do not duplicate the logo elsewhere.\n";
        $prompt .= "The ad text must be clearly readable and spelled correctly, and MUST appear EXACTLY as written (do not change wording, spelling):\n\"{$ad->text}\"\n";
        if ($instructions !== '') {
            $prompt .= "User instructions (follow these while keeping the ad text EXACTLY as written):\n{$instructions}\n";
        }
        $prompt .= "Minimal layout, high contrast, professional typography, safe margins.";

        $ad->forceFill([
            'prompt' => $prompt,
            'prompt_version' => 'v1',
            'brand_snapshot' => $brandSnapshot,
        ])->save();

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
            $prompt .= "Product image priority: The product reference images are provided in PRIORITY order. The FIRST product image is the PRIMARY image and should be the most prominent. The following images are secondary and can be smaller.\n";
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

            $promptTokens = isset($imagePart['promptTokens']) && is_numeric($imagePart['promptTokens'])
                ? (int) $imagePart['promptTokens']
                : null;
            $outputTokens = isset($imagePart['outputTokens']) && is_numeric($imagePart['outputTokens'])
                ? (int) $imagePart['outputTokens']
                : null;
            $totalTokens = isset($imagePart['totalTokens']) && is_numeric($imagePart['totalTokens'])
                ? (int) $imagePart['totalTokens']
                : null;

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
                'prompt_tokens' => $promptTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
                'debug' => $debug,
            ])->save();

            event(new AdUpdated(
                (int) $company->id,
                (string) $ad->id,
                (string) $ad->status,
                '/storage/' . ltrim((string) $relative, '/'),
                $ad->updated_at?->toISOString(),
            ));
        } catch (\Throwable $e) {
            Log::error('GenerateAdImageJob failed', [
                'adId' => $this->adId,
                'companyId' => $company->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $ad->forceFill([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ])->save();

            event(new AdUpdated(
                (int) $company->id,
                (string) $ad->id,
                (string) $ad->status,
                null,
                $ad->updated_at?->toISOString(),
            ));
        } finally {
            Storage::disk('local')->deleteDirectory('tmp/ad-input/' . $ad->id);
        }
    }
}
