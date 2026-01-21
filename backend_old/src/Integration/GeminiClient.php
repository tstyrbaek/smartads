<?php

declare(strict_types=1);

namespace SmartAdd\Integration;

final class GeminiClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta',
    ) {
    }

    /**
     * @param array<int, array{mimeType: string, data: string}> $referenceImages
     * @return array{mimeType: string, data: string}
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
        $res = $this->request($url, $payload);

        $candidates = $res['candidates'] ?? null;
        if (!is_array($candidates) || $candidates === []) {
            throw new \RuntimeException('gemini_missing_candidates');
        }

        $content = $candidates[0]['content'] ?? null;
        $partsOut = is_array($content) ? ($content['parts'] ?? null) : null;
        if (!is_array($partsOut)) {
            throw new \RuntimeException('gemini_missing_parts');
        }

        foreach ($partsOut as $part) {
            if (!is_array($part)) {
                continue;
            }
            $inline = $part['inlineData'] ?? null;
            if (!is_array($inline)) {
                continue;
            }
            $mimeType = $inline['mimeType'] ?? null;
            $data = $inline['data'] ?? null;
            if (is_string($mimeType) && $mimeType !== '' && is_string($data) && $data !== '') {
                return [
                    'mimeType' => $mimeType,
                    'data' => $data,
                ];
            }
        }

        throw new \RuntimeException('gemini_missing_inline_image');
    }

    private function request(string $url, array $json): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('curl_init_failed');
        }

        $body = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            throw new \RuntimeException('json_encode_failed');
        }

        $headers = [
            'x-goog-api-key: ' . $this->apiKey,
            'Content-Type: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('curl_exec_failed: ' . $err);
        }

        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('gemini_invalid_json');
        }

        if ($code < 200 || $code >= 300) {
            $msg = $decoded['error']['message'] ?? null;
            $msgStr = is_string($msg) && $msg !== '' ? $msg : ('http_' . $code);
            throw new \RuntimeException((string) $msgStr);
        }

        return $decoded;
    }
}
