<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Scraping\UrlSafety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProxyImageController extends Controller
{
    public function proxy(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        $url = $validated['url'];

        $urlSafety = new UrlSafety();
        if (!$urlSafety->isSafe($url)) {
            return response()->json(['error' => 'unsafe_url'], 400);
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                    'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                    'Accept-Language' => 'da-DK,da;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Referer' => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/',
                ])
                ->get($url);

            if (!$response->successful()) {
                return response()->json(['error' => 'fetch_failed'], 500);
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';
            $body = $response->body();

            return response($body, 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return response()->json(['error' => 'proxy_failed'], 500);
        }
    }
}
