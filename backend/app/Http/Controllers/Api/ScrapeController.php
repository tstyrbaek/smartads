<?php

namespace App\Http\Controllers\Api;

use App\Services\LLM\RecommendTextSpans;
use App\Services\Scraping\ScrapeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScrapeController
{
    public function __construct(
        private readonly ScrapeService $scrapeService,
        private readonly RecommendTextSpans $recommendTextSpans,
    ) {
    }

    public function scrape(Request $request): JsonResponse
    {
        Log::info('Scrape request received', [
            'url' => $request->input('url'),
            'has_auth' => $request->hasHeader('Authorization'),
            'has_company' => $request->hasHeader('X-Company-Id'),
        ]);
        
        $companyId = (int) $request->attributes->get('active_company_id');

        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2000'],
        ]);

        $url = trim((string) $validated['url']);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'invalid_url'], 422);
        }

        try {
            $result = $this->scrapeService->scrape($companyId, $url);

            $fullText = (string) ($result['full_text'] ?? '');
            $structured = is_array($result['structured'] ?? null) ? (array) $result['structured'] : [];

            $spans = [];
            if ($fullText !== '') {
                $spans = $this->recommendTextSpans->recommend($fullText, $structured);
            }

            return response()->json([
                'ok' => true,
                'result' => $result,
                'recommended_text_spans' => $spans,
            ]);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            
            \Log::error('Scrape failed', [
                'url' => $url,
                'company_id' => $companyId,
                'error' => $message,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            if (str_contains($message, 'unsupported_domain')) {
                return response()->json([
                    'error' => 'unsupported_domain',
                    'message' => 'Dette domæne understøttes ikke endnu. Vi understøtter pt. Bilbasen, Boligsiden, DBA, Sebiler, Hunique, EDC, Danbolig, BMC Leasing, Hjemmehos, Elsalg og Cykelcenter Midtjylland.',
                ], 422);
            }
            
            if (str_contains($message, 'HTTP 403')) {
                return response()->json([
                    'error' => 'access_denied',
                    'message' => 'Websiden blokerer automatisk hentning af indhold. Prøv en anden URL eller kopier indholdet manuelt.',
                ], 422);
            }
            
            if (str_contains($message, 'fetch_failed')) {
                return response()->json([
                    'error' => 'fetch_failed',
                    'message' => 'Kunne ikke hente siden. Tjek at URL\'en er korrekt og tilgængelig.',
                ], 422);
            }

            return response()->json([
                'error' => 'scrape_failed',
                'message' => 'Der opstod en fejl ved hentning af indhold.',
            ], 422);
        }
    }
}
