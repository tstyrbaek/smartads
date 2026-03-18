<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LLM\OpenAIClient;
use Illuminate\Http\Request;

class OptimizeTextController extends Controller
{
    public function optimize(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        $text = trim($validated['text']);
        if ($text === '') {
            return response()->json(['error' => 'empty_text'], 400);
        }

        $openai = new OpenAIClient();

        $systemPrompt = <<<PROMPT
Du er en professionel copywriter der optimerer annoncetekster.

Din opgave:
- Ret stavefejl og grammatik
- Gør teksten mere klar og præcis
- Behold den originale tone og budskab
- Gør teksten mere salgsorienteret hvis relevant
- Behold faktuelle oplysninger (priser, datoer, tal)
- Returner KUN den optimerede tekst, ingen forklaringer

Vigtige regler:
- Behold dansk sprog
- Behold alle faktuelle detaljer
- Gør ikke teksten længere end nødvendigt
- Brug ikke emojis med mindre de allerede er i teksten
PROMPT;

        $userPrompt = "Optimer følgende annoncetekst:\n\n" . $text;

        try {
            $response = $openai->chatCompletions([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ], ['temperature' => 0.7]);

            $optimizedText = trim((string) ($response['choices'][0]['message']['content'] ?? ''));
            if ($optimizedText === '') {
                return response()->json(['error' => 'empty_response'], 500);
            }

            return response()->json([
                'ok' => true,
                'original' => $text,
                'optimized' => $optimizedText,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'optimization_failed', 'message' => $e->getMessage()], 500);
        }
    }
}
