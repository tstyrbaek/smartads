<?php

namespace App\Services\LLM;

class RecommendTextSpans
{
    public function __construct(private readonly OpenAIClient $client)
    {
    }
    public function recommend(string $fullText, array $structured = []): array
    {
        $fullText = trim($fullText);
        if ($fullText === '') {
            return [];
        }

        $structuredJson = json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($structuredJson)) {
            $structuredJson = '{}';
        }

        $system = 'You are a helper that selects the most relevant parts of scraped page text to use as input for generating an advertisement. Return ONLY JSON.';

        $user = "FULL_TEXT:\n" . $fullText . "\n\nSTRUCTURED_DATA_JSON:\n" . $structuredJson . "\n\nTASK:\nPick the best parts of FULL_TEXT that describe the item/service (title, key facts, description, price, features). Output a JSON array of spans with character indices into FULL_TEXT, in the form: [{\"start\":0,\"end\":10}, ...]. Prefer 2-6 spans. Do not overlap spans. Keep total selected text under 1200 characters.";

        $json = $this->client->chatCompletions([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], [
            'temperature' => 0.1,
            'max_tokens' => 500,
        ]);

        $content = $json['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return [];
        }

        $len = mb_strlen($fullText, 'UTF-8');
        $spans = [];

        foreach ($decoded as $span) {
            if (!is_array($span)) {
                continue;
            }
            $start = isset($span['start']) ? (int) $span['start'] : null;
            $end = isset($span['end']) ? (int) $span['end'] : null;
            if ($start === null || $end === null) {
                continue;
            }
            if ($start < 0 || $end <= $start) {
                continue;
            }
            if ($end > $len) {
                $end = $len;
            }
            $spans[] = ['start' => $start, 'end' => $end];
        }

        usort($spans, fn ($a, $b) => $a['start'] <=> $b['start']);

        $deduped = [];
        $lastEnd = -1;
        foreach ($spans as $span) {
            if ($span['start'] < $lastEnd) {
                continue;
            }
            $deduped[] = $span;
            $lastEnd = $span['end'];
        }

        return $deduped;
    }
}
