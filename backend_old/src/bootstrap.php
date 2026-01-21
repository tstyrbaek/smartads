<?php

declare(strict_types=1);

use SmartAdd\Domain\AdsService;
use SmartAdd\Domain\BrandService;
use SmartAdd\Http\Request;
use SmartAdd\Http\Response;
use SmartAdd\Http\Router;
use SmartAdd\Integration\GeminiClient;
use SmartAdd\Integration\NanoBananaClient;
use SmartAdd\Util\ArrayUtil;

 $autoload = dirname(__DIR__) . '/vendor/autoload.php';
 if (is_file($autoload)) {
     require $autoload;
 } else {
     spl_autoload_register(static function (string $class): void {
         $prefix = 'SmartAdd\\';
         if (!str_starts_with($class, $prefix)) {
             return;
         }

         $relativeClass = substr($class, strlen($prefix));
         $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
         if (is_file($file)) {
             require $file;
         }
     });
 }

$configFile = dirname(__DIR__) . '/config.php';
$config = [];
if (is_file($configFile)) {
    $config = require $configFile;
}

$router = new Router();

$corsHeaders = [
    'access-control-allow-origin' => '*',
    'access-control-allow-headers' => 'content-type',
    'access-control-allow-methods' => 'GET,POST,OPTIONS',
];

$storageDir = dirname(__DIR__) . '/storage';
$brandService = new BrandService($storageDir);
$adsService = new AdsService($storageDir);

$getPublicBaseUrl = static function (): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
};

$overlayBrandLogo = static function (string $generatedPngAbsolutePath, string $brandLogoAbsolutePath): void {
    if (!is_file($generatedPngAbsolutePath) || !is_file($brandLogoAbsolutePath)) {
        return;
    }

    if (!function_exists('imagecreatefrompng')) {
        return;
    }

    $ext = strtolower((string) pathinfo($brandLogoAbsolutePath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
        return;
    }

    $canvas = @imagecreatefrompng($generatedPngAbsolutePath);
    if ($canvas === false) {
        return;
    }

    $logo = false;
    if ($ext === 'png') {
        $logo = @imagecreatefrompng($brandLogoAbsolutePath);
    } elseif ($ext === 'jpg' || $ext === 'jpeg') {
        $logo = @imagecreatefromjpeg($brandLogoAbsolutePath);
    } elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
        $logo = @imagecreatefromwebp($brandLogoAbsolutePath);
    }

    if ($logo === false) {
        imagedestroy($canvas);
        return;
    }

    imagealphablending($canvas, true);
    imagesavealpha($canvas, true);

    $cw = imagesx($canvas);
    $ch = imagesy($canvas);
    $lw = imagesx($logo);
    $lh = imagesy($logo);

    if ($cw <= 0 || $ch <= 0 || $lw <= 0 || $lh <= 0) {
        imagedestroy($logo);
        imagedestroy($canvas);
        return;
    }

    $padding = max(8, (int) round($cw * 0.03));
    $maxLogoW = (int) round($cw * 0.22);
    $maxLogoH = (int) round($ch * 0.22);

    $scale = min($maxLogoW / $lw, $maxLogoH / $lh, 1.0);
    $tw = max(1, (int) floor($lw * $scale));
    $th = max(1, (int) floor($lh * $scale));

    $tmp = imagecreatetruecolor($tw, $th);
    imagealphablending($tmp, false);
    imagesavealpha($tmp, true);
    $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
    imagefilledrectangle($tmp, 0, 0, $tw, $th, $transparent);
    imagecopyresampled($tmp, $logo, 0, 0, 0, 0, $tw, $th, $lw, $lh);

    $x = $padding;
    $y = $padding;

    imagecopy($canvas, $tmp, $x, $y, 0, 0, $tw, $th);

    @imagepng($canvas, $generatedPngAbsolutePath);

    imagedestroy($tmp);
    imagedestroy($logo);
    imagedestroy($canvas);
};

$assertPublicImageUrl = static function (string $url): void {
    $path = parse_url($url, PHP_URL_PATH);
    $ext = strtolower((string) pathinfo(is_string($path) ? $path : '', PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
        throw new RuntimeException('logo_url_must_end_with_image_extension: ' . $url);
    }

    $ch = curl_init();
    if ($ch === false) {
        throw new RuntimeException('curl_init_failed');
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $ok = curl_exec($ch);
    if ($ok === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('logo_url_not_reachable: ' . $err);
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('logo_url_http_' . $status . ': ' . $url);
    }

    if (!str_starts_with(strtolower($contentType), 'image/')) {
        throw new RuntimeException('logo_url_invalid_content_type: ' . $contentType . ' url=' . $url);
    }

    $ch = curl_init();
    if ($ch === false) {
        throw new RuntimeException('curl_init_failed');
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RANGE, '0-15');

    $body = curl_exec($ch);
    if ($body === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('logo_url_not_reachable: ' . $err);
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('logo_url_http_' . $status . ': ' . $url);
    }

    if ($ext === 'png') {
        if (strlen($body) < 8 || substr($body, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new RuntimeException('logo_url_invalid_png_signature: ' . $url);
        }
    }
};

$createLogoReferencePng = static function (string $sourceAbsolutePath, string $targetAbsolutePath): void {
    if (!function_exists('imagecreatefrompng')) {
        throw new RuntimeException('gd_not_available');
    }

    $ext = strtolower((string) pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
        throw new RuntimeException('logo_ref_unsupported_format');
    }

    $img = false;
    if ($ext === 'png') {
        $img = @imagecreatefrompng($sourceAbsolutePath);
    } elseif ($ext === 'jpg' || $ext === 'jpeg') {
        $img = @imagecreatefromjpeg($sourceAbsolutePath);
    } elseif ($ext === 'webp') {
        if (!function_exists('imagecreatefromwebp')) {
            throw new RuntimeException('gd_webp_not_available');
        }
        $img = @imagecreatefromwebp($sourceAbsolutePath);
    }

    if ($img === false) {
        throw new RuntimeException('logo_ref_decode_failed');
    }

    $w = imagesx($img);
    $h = imagesy($img);
    if ($w <= 0 || $h <= 0) {
        imagedestroy($img);
        throw new RuntimeException('logo_ref_invalid_dimensions');
    }

    $canvas = imagecreatetruecolor($w, $h);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $w, $h, $transparent);
    imagecopy($canvas, $img, 0, 0, 0, 0, $w, $h);

    if (@imagepng($canvas, $targetAbsolutePath) === false) {
        imagedestroy($img);
        imagedestroy($canvas);
        throw new RuntimeException('logo_ref_write_failed');
    }

    imagedestroy($img);
    imagedestroy($canvas);
};

$ensurePngBinary = static function (string $mimeType, string $binary): string {
    if ($mimeType === 'image/png') {
        return $binary;
    }

    if (!function_exists('imagecreatefromstring')) {
        throw new RuntimeException('gd_not_available');
    }

    $img = @imagecreatefromstring($binary);
    if ($img === false) {
        throw new RuntimeException('generated_image_decode_failed');
    }

    ob_start();
    $ok = @imagepng($img);
    $out = ob_get_clean();
    imagedestroy($img);

    if ($ok === false || !is_string($out) || $out === '') {
        throw new RuntimeException('generated_image_encode_png_failed');
    }

    return $out;
};

$extractResultImageUrl = static function (array $payload): ?string {
    $candidates = [
        'resultImageUrl',
        'result_image_url',
        'resultImageURL',
    ];

    foreach ($candidates as $key) {
        $url = ArrayUtil::deepFindKey($payload, $key);
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    $urls = ArrayUtil::deepFindKey($payload, 'resultImageUrls');
    if (is_array($urls) && isset($urls[0]) && is_string($urls[0]) && $urls[0] !== '') {
        return $urls[0];
    }
    $urls = ArrayUtil::deepFindKey($payload, 'result_image_urls');
    if (is_array($urls) && isset($urls[0]) && is_string($urls[0]) && $urls[0] !== '') {
        return $urls[0];
    }

    return null;
};

$router->get('/api/health', static function (Request $request, array $params, array $config) use ($corsHeaders): Response {
    return Response::json(["ok" => true], 200, $corsHeaders);
});

$router->get('/', static function (Request $request, array $params, array $config) use ($corsHeaders): Response {
    return Response::text('SmartAdd backend running', 200, $corsHeaders);
});

$router->get('/api/brand', static function (Request $request, array $params, array $config) use ($brandService, $corsHeaders): Response {
    return Response::json($brandService->get(), 200, $corsHeaders);
});

$router->post('/api/brand', static function (Request $request, array $params, array $config) use ($brandService, $corsHeaders, $createLogoReferencePng): Response {
    $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

    $companyName = (string) ($request->post['companyName'] ?? '');
    $companyDescription = (string) ($request->post['companyDescription'] ?? '');
    $audienceDescription = (string) ($request->post['audienceDescription'] ?? '');
    $primaryColor1 = (string) ($request->post['primaryColor1'] ?? '');
    $primaryColor2 = (string) ($request->post['primaryColor2'] ?? '');

    if ($companyName === '' || $primaryColor1 === '' || $primaryColor2 === '') {
        return Response::json(['error' => 'missing_required_fields'], 400, $corsHeaders);
    }

    $brand = [
        'companyName' => $companyName,
        'companyDescription' => $companyDescription,
        'audienceDescription' => $audienceDescription,
        'primaryColor1' => $primaryColor1,
        'primaryColor2' => $primaryColor2,
    ];

    $file = $request->files['logo'] ?? null;
    if (is_array($file) && isset($file['tmp_name'], $file['name']) && is_string($file['tmp_name']) && is_string($file['name']) && $file['tmp_name'] !== '') {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return Response::json(['error' => 'invalid_logo_type'], 400, $corsHeaders);
        }
        $uploadDir = dirname(__DIR__) . '/storage/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $target = $uploadDir . '/logo.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return Response::json(['error' => 'logo_upload_failed'], 500, $corsHeaders);
        }

        $brand['logoPath'] = '/storage/uploads/logo.' . $ext;

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            try {
                $refTarget = $uploadDir . '/logo_ref.png';
                $createLogoReferencePng($target, $refTarget);
                $brand['logoRefPath'] = '/storage/uploads/logo_ref.png';
            } catch (Throwable $e) {
                return Response::json(['error' => 'logo_ref_generation_failed', 'detail' => $e->getMessage()], 500, $corsHeaders);
            }
        }
    } else {
        $existing = $brandService->get();
        if (is_array($existing) && isset($existing['logoPath'])) {
            $brand['logoPath'] = $existing['logoPath'];
        }
        if (is_array($existing) && isset($existing['logoRefPath'])) {
            $brand['logoRefPath'] = $existing['logoRefPath'];
        }
    }

    return Response::json($brandService->save($brand), 200, $corsHeaders);
});

$router->post('/api/ads', static function (Request $request, array $params, array $config) use ($adsService, $brandService, $corsHeaders, $getPublicBaseUrl, $assertPublicImageUrl, $ensurePngBinary, $overlayBrandLogo): Response {
    $data = $request->json() ?? [];
    $text = (string) ($data['text'] ?? ($request->post['text'] ?? ''));
    $debugRequested = ($data['debug'] ?? ($request->post['debug'] ?? null)) === true
        || ($data['debug'] ?? ($request->post['debug'] ?? null)) === 1
        || ($data['debug'] ?? ($request->post['debug'] ?? null)) === '1';
    if (trim($text) === '') {
        return Response::json(['error' => 'missing_text'], 400, $corsHeaders);
    }

    $brand = $brandService->get();
    $logoPath = (string) ($brand['logoPath'] ?? '');
    $logoRefPath = (string) ($brand['logoRefPath'] ?? '');
    if ($logoPath === '') {
        return Response::json(['error' => 'brand_logo_missing'], 400, $corsHeaders);
    }

    $ad = $adsService->create($text);
    $ad['status'] = 'generating';
    $ad['updatedAt'] = date(DATE_ATOM);

    try {
        $gemini = $config['gemini'] ?? [];
        $geminiApiKey = (string) (is_array($gemini) ? ($gemini['api_key'] ?? '') : '');

        $nanobanana = $config['nanobanana'] ?? [];
        $apiKey = (string) ($nanobanana['api_key'] ?? '');
        $baseUrl = (string) ($nanobanana['base_url'] ?? '');
        $callbackUrl = (string) ($nanobanana['callback_url'] ?? '');
        $mode = (string) ($nanobanana['mode'] ?? 'standard');

        $prompt = "Create a clean, modern square 1:1 web advertisement image.\n" .
            "Use the brand primary colors: {$brand['primaryColor1']} and {$brand['primaryColor2']}.\n" .
            "Company description: {$brand['companyDescription']}.\n" .
            "Target audience: {$brand['audienceDescription']}.\n" .
            "Product images: Use the provided reference images (in addition to the logo) as the product/subject photos inside the ad composition. Place at least one of these product images clearly in the design (do not ignore them).\n" .
            "Do not add extra products that are not present in the provided product images. Do not copy any text from the product images.\n" .
            "Logo: Use the provided reference logo image (do not invent a new logo). Include EXACTLY ONE logo in the design. Do not duplicate the logo elsewhere.\n" .
            "The ad text must be clearly readable and spelled correctly, and MUST appear EXACTLY as written (do not change wording, spelling):\n" .
            "\"{$text}\"\n" .
            "Minimal layout, high contrast, professional typography, safe margins.";

        if ($geminiApiKey !== '') {
            $logoPathForGemini = $logoRefPath !== '' ? $logoRefPath : $logoPath;
            $logoAbsolute = dirname(__DIR__) . $logoPathForGemini;
            if (!is_file($logoAbsolute)) {
                throw new RuntimeException('brand_logo_file_missing');
            }

            $logoExt = strtolower((string) pathinfo($logoAbsolute, PATHINFO_EXTENSION));
            $logoMime = match ($logoExt) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };

            $logoBin = file_get_contents($logoAbsolute);
            if ($logoBin === false || $logoBin === '') {
                throw new RuntimeException('brand_logo_file_read_failed');
            }

            $referenceImages = [
                [
                    'mimeType' => $logoMime,
                    'data' => base64_encode($logoBin),
                ],
            ];

            $uploadedImages = $request->files['images'] ?? null;
            if (is_array($uploadedImages) && isset($uploadedImages['tmp_name'], $uploadedImages['name'])) {
                $tmpNames = $uploadedImages['tmp_name'];
                $names = $uploadedImages['name'];
                $types = $uploadedImages['type'] ?? [];

                if (is_array($tmpNames) && is_array($names)) {
                    $max = min(3, count($tmpNames));
                    for ($i = 0; $i < $max; $i++) {
                        $tmp = $tmpNames[$i] ?? null;
                        $name = $names[$i] ?? null;
                        $type = is_array($types) ? ($types[$i] ?? null) : null;
                        if (!is_string($tmp) || $tmp === '' || !is_string($name) || $name === '') {
                            continue;
                        }
                        if (!is_file($tmp)) {
                            continue;
                        }

                        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
                        $mime = is_string($type) && $type !== '' ? $type : match ($ext) {
                            'png' => 'image/png',
                            'jpg', 'jpeg' => 'image/jpeg',
                            'webp' => 'image/webp',
                            default => 'application/octet-stream',
                        };

                        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
                            continue;
                        }

                        $bin = file_get_contents($tmp);
                        if ($bin === false || $bin === '') {
                            continue;
                        }

                        $referenceImages[] = [
                            'mimeType' => $mime,
                            'data' => base64_encode($bin),
                        ];
                    }
                }
            }

            $productImagesCount = max(0, count($referenceImages) - 1);
            if ($productImagesCount > 0) {
                $prompt .= "\n" .
                    "Product images requirement: You MUST include ALL provided product reference images ({$productImagesCount}) in the final ad.\n" .
                    "Layout requirement: Show all product images clearly (for example as a collage/grid or multiple tiles). Do not omit any of them.\n";
            }

            $model = (string) (is_array($gemini) ? ($gemini['model'] ?? 'gemini-3-pro-image-preview') : 'gemini-3-pro-image-preview');
            $aspectRatio = (string) (is_array($gemini) ? ($gemini['aspect_ratio'] ?? '1:1') : '1:1');
            $imageSize = (string) (is_array($gemini) ? ($gemini['image_size'] ?? '1K') : '1K');

            $client = new GeminiClient($geminiApiKey);
            $imagePart = $client->generateImage($model, $prompt, $referenceImages, $aspectRatio, $imageSize);

            $generatedBin = base64_decode((string) ($imagePart['data'] ?? ''), true);
            if (!is_string($generatedBin) || $generatedBin === '') {
                throw new RuntimeException('gemini_image_decode_failed');
            }

            $generatedPng = $ensurePngBinary((string) ($imagePart['mimeType'] ?? 'application/octet-stream'), $generatedBin);

            $generatedDir = dirname(__DIR__) . '/storage/generated';
            if (!is_dir($generatedDir)) {
                mkdir($generatedDir, 0775, true);
            }

            $relative = '/storage/generated/' . $ad['id'] . '.png';
            $absolute = dirname(__DIR__) . $relative;
            if (file_put_contents($absolute, $generatedPng) === false) {
                throw new RuntimeException('generated_image_write_failed');
            }

            $ad['status'] = 'success';
            $ad['localFilePath'] = $relative;
            $ad['error'] = null;
            $ad['debug'] = [
                'geminiRequest' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'aspectRatio' => $aspectRatio,
                    'imageSize' => $imageSize,
                    'referenceImage' => [
                        'mimeType' => $logoMime,
                        'path' => $logoPathForGemini,
                        'bytes' => strlen($logoBin),
                    ],
                    'referenceImagesCount' => count($referenceImages),
                    'productImagesCount' => $productImagesCount,
                ],
            ];

            $adsService->upsert($ad);

            $payload = ['adId' => $ad['id'], 'status' => $ad['status']];
            if ($debugRequested) {
                $payload['debug'] = $ad['debug'];
            }

            return Response::json($payload, 200, $corsHeaders);
        }

        if ($apiKey === '' || $baseUrl === '' || $callbackUrl === '') {
            throw new RuntimeException('nanobanana_config_missing');
        }

        $client = new NanoBananaClient($baseUrl, $apiKey);

        $logoPathForPro = $logoRefPath !== '' ? $logoRefPath : $logoPath;
        $logoUrl = $getPublicBaseUrl() . $logoPathForPro;
        $publicBaseUrl = (string) ($nanobanana['public_base_url'] ?? '');
        $publicLogoUrl = (string) ($nanobanana['public_logo_url'] ?? '');

        if ($publicBaseUrl === '') {
            $cbParts = parse_url($callbackUrl);
            $cbScheme = is_array($cbParts) ? ($cbParts['scheme'] ?? null) : null;
            $cbHost = is_array($cbParts) ? ($cbParts['host'] ?? null) : null;
            $cbPort = is_array($cbParts) ? ($cbParts['port'] ?? null) : null;

            if (is_string($cbScheme) && is_string($cbHost) && $cbScheme !== '' && $cbHost !== '') {
                $publicBaseUrl = $cbScheme . '://' . $cbHost;
                if (is_int($cbPort)) {
                    $publicBaseUrl .= ':' . $cbPort;
                }
            }
        }

        if ($publicBaseUrl === '') {
            throw new RuntimeException('nanobanana_pro_requires_public_base_url');
        }

        $logoReferenceUrl = rtrim($publicBaseUrl, '/') . $logoPathForPro;

        $imageUrls = [];
        $nanobananaRequest = [
            'mode' => $mode,
            'callbackUrl' => $callbackUrl,
            'prompt' => $prompt,
        ];

        $attempts = [];
        $maxAttempts = 3;

        if ($mode === 'pro') {
            $assertPublicImageUrl($logoReferenceUrl);
            $imageUrls = [$logoReferenceUrl];
            $proOptions = [
                'imageUrls' => $imageUrls,
                'resolution' => (string) ($nanobanana['resolution'] ?? '1K'),
                'aspectRatio' => '1:1',
            ];
            $nanobananaRequest['options'] = $proOptions;

            $taskId = '';
            for ($i = 1; $i <= $maxAttempts; $i++) {
                try {
                    $taskId = $client->createTaskPro($prompt, $callbackUrl, $proOptions);
                    break;
                } catch (Throwable $e) {
                    $attempts[] = [
                        'attempt' => $i,
                        'error' => $e->getMessage(),
                        'time' => date(DATE_ATOM),
                    ];

                    if ($i < $maxAttempts) {
                        usleep(250000 * $i);
                        continue;
                    }

                    throw $e;
                }
            }
        } else {
            $standardOptions = [
                'numImages' => 1,
                'image_size' => '1:1',
            ];
            $nanobananaRequest['options'] = $standardOptions;

            $taskId = '';
            for ($i = 1; $i <= $maxAttempts; $i++) {
                try {
                    $taskId = $client->createTask($prompt, $callbackUrl, $standardOptions);
                    break;
                } catch (Throwable $e) {
                    $attempts[] = [
                        'attempt' => $i,
                        'error' => $e->getMessage(),
                        'time' => date(DATE_ATOM),
                    ];

                    if ($i < $maxAttempts) {
                        usleep(250000 * $i);
                        continue;
                    }

                    throw $e;
                }
            }
        }

        if ($attempts !== []) {
            $nanobananaRequest['attempts'] = $attempts;
        }

        $ad['debug'] = [
            'mode' => $mode,
            'prompt' => $prompt,
            'logoReferenceUrl' => $logoReferenceUrl,
            'imageUrls' => $imageUrls,
            'publicBaseUrl' => $publicBaseUrl,
            'nanobananaRequest' => $nanobananaRequest,
        ];

        $ad['nanobananaTaskId'] = $taskId;
    } catch (Throwable $e) {
        $ad['status'] = 'failed';
        $ad['error'] = $e->getMessage();
    }

    $adsService->upsert($ad);

    $payload = ['adId' => $ad['id'], 'status' => $ad['status']];
    if ($debugRequested) {
        $payload['debug'] = is_array($ad['debug'] ?? null) ? $ad['debug'] : null;
    }

    return Response::json($payload, 200, $corsHeaders);
});

$router->get('/api/ads/{id}', static function (Request $request, array $params, array $config) use ($adsService, $corsHeaders, $extractResultImageUrl, $brandService, $overlayBrandLogo): Response {
    $id = (string) ($params['id'] ?? '');
    $ad = $adsService->find($id);
    if (!is_array($ad)) {
        return Response::json(['error' => 'not_found'], 404, $corsHeaders);
    }

    if (($ad['status'] ?? '') === 'generating' && is_string($ad['nanobananaTaskId'] ?? null) && $ad['nanobananaTaskId'] !== '') {
        try {
            $nanobanana = $config['nanobanana'] ?? [];
            $apiKey = (string) ($nanobanana['api_key'] ?? '');
            $baseUrl = (string) ($nanobanana['base_url'] ?? '');

            if ($apiKey !== '' && $baseUrl !== '') {
                $client = new NanoBananaClient($baseUrl, $apiKey);
                $status = $client->getRecordInfo((string) $ad['nanobananaTaskId']);

                $successFlag = ArrayUtil::deepFindKey($status, 'successFlag');
                if ($successFlag === 1 || $successFlag === '1') {
                    $resultImageUrl = $extractResultImageUrl($status);

                    if (is_string($resultImageUrl) && $resultImageUrl !== '') {
                        $generatedDir = dirname(__DIR__) . '/storage/generated';
                        if (!is_dir($generatedDir)) {
                            mkdir($generatedDir, 0775, true);
                        }

                        $relative = '/storage/generated/' . $ad['id'] . '.png';
                        $absolute = dirname(__DIR__) . $relative;
                        $png = @file_get_contents($resultImageUrl);
                        if ($png !== false && file_put_contents($absolute, $png) !== false) {
                            $brand = $brandService->get();
                            $brandLogoPath = (string) ($brand['logoPath'] ?? '');
                            if ($brandLogoPath !== '') {
                                $overlayBrandLogo($absolute, dirname(__DIR__) . $brandLogoPath);
                            }
                            $ad['status'] = 'success';
                            $ad['resultImageUrl'] = $resultImageUrl;
                            $ad['localFilePath'] = $relative;
                            $ad['error'] = null;
                        } else {
                            $ad['status'] = 'failed';
                            $ad['error'] = 'download_generated_image_failed';
                        }
                    }
                } elseif ($successFlag === 2 || $successFlag === '2' || $successFlag === 3 || $successFlag === '3') {
                    $ad['status'] = 'failed';
                    $err = ArrayUtil::deepFindKey($status, 'errorMessage');
                    $ad['error'] = is_string($err) && $err !== '' ? $err : 'generation_failed';
                }

                $ad['updatedAt'] = date(DATE_ATOM);
                $adsService->upsert($ad);
            }
        } catch (Throwable $e) {
            $ad['updatedAt'] = date(DATE_ATOM);
            $ad['error'] = $e->getMessage();
            $adsService->upsert($ad);
        }
    }

    $downloadUrl = null;
    $previewUrl = null;
    if (($ad['status'] ?? '') === 'success' && is_string($ad['localFilePath'] ?? null) && $ad['localFilePath'] !== '') {
        $downloadUrl = '/api/ads/' . $id . '/download';
        $previewUrl = '/api/ads/' . $id . '/image';
    }

    $debug = null;
    if (($request->query['debug'] ?? null) === '1' || ($request->query['debug'] ?? null) === 1) {
        $debug = is_array($ad['debug'] ?? null) ? $ad['debug'] : null;
    }

    return Response::json([
        'ad' => $ad,
        'downloadUrl' => $downloadUrl,
        'previewUrl' => $previewUrl,
        'debug' => $debug,
    ], 200, $corsHeaders);
});

$router->get('/api/ads/{id}/download', static function (Request $request, array $params, array $config) use ($adsService, $corsHeaders): Response {
    $id = (string) ($params['id'] ?? '');
    $ad = $adsService->find($id);
    if (!is_array($ad)) {
        return Response::json(['error' => 'not_found'], 404, $corsHeaders);
    }

    $local = (string) ($ad['localFilePath'] ?? '');
    if ($local === '') {
        return Response::json(['error' => 'not_ready'], 409, $corsHeaders);
    }

    $absolute = dirname(__DIR__) . $local;
    return Response::file($absolute, $id . '.png', 'image/png', $corsHeaders);
});

$router->get('/api/ads/{id}/image', static function (Request $request, array $params, array $config) use ($adsService, $corsHeaders): Response {
    $id = (string) ($params['id'] ?? '');
    $ad = $adsService->find($id);
    if (!is_array($ad)) {
        return Response::json(['error' => 'not_found'], 404, $corsHeaders);
    }

    $local = (string) ($ad['localFilePath'] ?? '');
    if ($local === '') {
        return Response::json(['error' => 'not_ready'], 409, $corsHeaders);
    }

    $absolute = dirname(__DIR__) . $local;
    if (!is_file($absolute)) {
        return Response::json(['error' => 'file_not_found'], 404, $corsHeaders);
    }

    $body = file_get_contents($absolute);
    if ($body === false) {
        return Response::json(['error' => 'file_read_failed'], 500, $corsHeaders);
    }

    return new Response(200, array_merge(['content-type' => 'image/png'], $corsHeaders), $body);
});

$router->post('/api/nanobanana/callback', static function (Request $request, array $params, array $config) use ($adsService, $corsHeaders, $extractResultImageUrl, $brandService, $overlayBrandLogo): Response {
    $payload = $request->json() ?? [];

    $taskId = ArrayUtil::deepFindKey($payload, 'taskId');
    if (!is_string($taskId) || $taskId === '') {
        return Response::json(['error' => 'missing_task_id'], 400, $corsHeaders);
    }

    $resultImageUrl = $extractResultImageUrl($payload);

    $ads = $adsService->all();
    $target = null;
    foreach ($ads as $ad) {
        if (($ad['nanobananaTaskId'] ?? null) === $taskId) {
            $target = $ad;
            break;
        }
    }

    if (!is_array($target)) {
        return Response::json(['error' => 'ad_not_found_for_task'], 404, $corsHeaders);
    }

    $target['updatedAt'] = date(DATE_ATOM);

    if (is_string($resultImageUrl) && $resultImageUrl !== '') {
        $target['resultImageUrl'] = $resultImageUrl;
        $generatedDir = dirname(__DIR__) . '/storage/generated';
        if (!is_dir($generatedDir)) {
            mkdir($generatedDir, 0775, true);
        }
        $relative = '/storage/generated/' . $target['id'] . '.png';
        $absolute = dirname(__DIR__) . $relative;

        $png = @file_get_contents($resultImageUrl);
        if ($png !== false && file_put_contents($absolute, $png) !== false) {
            $brand = $brandService->get();
            $brandLogoPath = (string) ($brand['logoPath'] ?? '');
            if ($brandLogoPath !== '') {
                $overlayBrandLogo($absolute, dirname(__DIR__) . $brandLogoPath);
            }
            $target['status'] = 'success';
            $target['localFilePath'] = $relative;
            $target['error'] = null;
        } else {
            $target['status'] = 'failed';
            $target['error'] = 'download_generated_image_failed';
        }
    } else {
        $target['status'] = 'failed';
        $target['error'] = 'missing_result_image_url';
    }

    $adsService->upsert($target);
    return Response::json(['ok' => true], 200, $corsHeaders);
});

$router->get('/storage/{path:.+}', static function (Request $request, array $params, array $config) use ($corsHeaders): Response {
    $relative = $params['path'] ?? '';
    if (!is_string($relative) || $relative === '' || str_contains($relative, '..')) {
        return Response::json(['error' => 'invalid_path'], 400, $corsHeaders);
    }

    $absolute = dirname(__DIR__) . '/storage/' . $relative;
    if (!is_file($absolute)) {
        return Response::json(['error' => 'not_found'], 404, $corsHeaders);
    }

    $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
    $contentType = match ($ext) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'application/octet-stream',
    };

    $body = file_get_contents($absolute);
    if ($body === false) {
        return Response::json(['error' => 'file_read_failed'], 500, $corsHeaders);
    }

    return new Response(200, array_merge(['content-type' => $contentType], $corsHeaders), $body);
});

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    Response::text('', 204, $corsHeaders)->send();
    return;
}

$router->dispatch(Request::fromGlobals(), $config)->send();
