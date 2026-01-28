<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class GeminiClient
{
    private const DEFAULT_TIMEOUT_SECONDS = 180;
    private const DEFAULT_CONNECT_TIMEOUT_SECONDS = 15;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta',
    ) {
    }

    /**
     * @param array<int, array{mimeType: string, data: string}> $referenceImages
     * @return array{mimeType: string, data: string, promptTokens?: int|null, outputTokens?: int|null, totalTokens?: int|null}
     */
    public function generateImage(
        string $model,
        string $prompt,
        array $referenceImages = [],
        string $aspectRatio = '1:1',
        string $imageSize = '1K',
    ): array {
        $parts = [
            ['text' => $prompt],
        ];

        foreach ($referenceImages as $referenceImage) {
            if (!is_array($referenceImage) || !isset($referenceImage['mimeType'], $referenceImage['data'])) {
                continue;
            }

            $mimeType = (string) $referenceImage['mimeType'];
            $data = (string) $referenceImage['data'];
            if ($mimeType === '' || $data === '') {
                continue;
            }

            $parts[] = [
                'inlineData' => [
                    'mimeType' => $mimeType,
                    'data' => $data,
                ],
            ];
        }

        $payload = [
            'contents' => [
                [
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['TEXT', 'IMAGE'],
                'imageConfig' => [
                    'aspectRatio' => $aspectRatio,
                    'imageSize' => $imageSize,
                ],
            ],
        ];

        $url = rtrim($this->baseUrl, '/') . '/models/' . rawurlencode($model) . ':generateContent';

        /** @var Response $res */
        $res = Http::retry(3, 750, throw: false)
            ->connectTimeout(self::DEFAULT_CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::DEFAULT_TIMEOUT_SECONDS)
            ->withHeaders([
            'x-goog-api-key' => $this->apiKey,
            'content-type' => 'application/json',
        ])->post($url, $payload);

        $json = json_decode((string) $res->body(), true);
        if (!is_array($json)) {
            throw new \RuntimeException('gemini_invalid_json');
        }

        if ($res->status() < 200 || $res->status() >= 300) {
            $msg = data_get($json, 'error.message');
            $msgStr = is_string($msg) && $msg !== '' ? $msg : ('http_' . $res->status());
            throw new \RuntimeException($msgStr);
        }

        $candidates = $json['candidates'] ?? null;
        if (!is_array($candidates) || $candidates === []) {
            throw new \RuntimeException('gemini_missing_candidates');
        }

        $partsOut = data_get($candidates, '0.content.parts');
        if (!is_array($partsOut)) {
            throw new \RuntimeException('gemini_missing_parts');
        }

        $promptTokens = data_get($json, 'usageMetadata.promptTokenCount');
        $outputTokens = data_get($json, 'usageMetadata.candidatesTokenCount');
        $totalTokens = data_get($json, 'usageMetadata.totalTokenCount');

        $promptTokensInt = is_numeric($promptTokens) ? (int) $promptTokens : null;
        $outputTokensInt = is_numeric($outputTokens) ? (int) $outputTokens : null;
        $totalTokensInt = is_numeric($totalTokens) ? (int) $totalTokens : null;

        foreach ($partsOut as $part) {
            if (!is_array($part)) {
                continue;
            }

            $mimeType = data_get($part, 'inlineData.mimeType');
            $data = data_get($part, 'inlineData.data');

            if (is_string($mimeType) && $mimeType !== '' && is_string($data) && $data !== '') {
                return [
                    'mimeType' => $mimeType,
                    'data' => $data,
                    'promptTokens' => $promptTokensInt,
                    'outputTokens' => $outputTokensInt,
                    'totalTokens' => $totalTokensInt,
                ];
            }
        }

        throw new \RuntimeException('gemini_missing_inline_image');
    }
}
