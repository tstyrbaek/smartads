<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GeminiWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $expectedSecret = (string) config('services.gemini.webhook_secret', '');
        if ($expectedSecret === '') {
            return response()->json(['error' => 'webhook_secret_not_configured'], 500);
        }

        $providedSecret = (string) $request->header('X-Webhook-Secret', '');
        if ($providedSecret === '' || !hash_equals($expectedSecret, $providedSecret)) {
            return response()->json(['error' => 'invalid_secret'], 401);
        }

        $payload = $request->all();

        Log::info('Gemini webhook received', [
            'headers' => [
                'content_type' => $request->header('content-type'),
                'user_agent' => $request->header('user-agent'),
            ],
            'payload' => $payload,
        ]);

        return response()->json(['ok' => true]);
    }
}
