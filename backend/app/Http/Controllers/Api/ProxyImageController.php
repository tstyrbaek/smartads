<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Scraping\UrlSafety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyImageController extends Controller
{
    public function proxy(Request $request)
    {
        Log::info('Proxy image request received', ['url' => $request->input('url')]);
        
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        $url = $validated['url'];

        // Basic URL validation - only allow http/https
        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            Log::warning('Proxy image invalid scheme', ['url' => $url]);
            return response()->json(['error' => 'invalid_url'], 400);
        }

        try {
            // Use cURL instead of Http facade to avoid potential issues
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: smartads_scraper/1.0',
                    'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                ],
            ]);

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($body === false || $error !== '') {
                Log::error('Proxy image cURL failed', ['url' => $url, 'error' => $error]);
                return response()->json(['error' => 'fetch_failed', 'curl_error' => $error], 500);
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                Log::error('Proxy image HTTP error', ['url' => $url, 'status' => $httpCode]);
                return response()->json(['error' => 'fetch_failed', 'status' => $httpCode], 500);
            }

            Log::info('Proxy image success', ['url' => $url, 'size' => strlen($body)]);

            return response($body, 200)
                ->header('Content-Type', $contentType ?: 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            Log::error('Proxy image exception', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'proxy_failed', 'message' => $e->getMessage()], 500);
        }
    }
}
