<?php

declare(strict_types=1);

namespace SmartAdd\Integration;

final class NanoBananaClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {
    }

    public function createTask(string $prompt, string $callbackUrl, array $options = []): string
    {
        $payload = array_merge([
            'prompt' => $prompt,
            'type' => 'TEXTTOIAMGE',
            'numImages' => 1,
            'callBackUrl' => $callbackUrl,
            'image_size' => '1:1',
        ], $options);

        $res = $this->request('POST', $this->baseUrl . '/generate', $payload);
        $taskId = $res['data']['taskId'] ?? null;
        if (!is_string($taskId) || $taskId === '') {
            throw new \RuntimeException('nanobanana_missing_task_id');
        }
        return $taskId;
    }

    public function createTaskPro(string $prompt, string $callbackUrl, array $options = []): string
    {
        $payload = array_merge([
            'prompt' => $prompt,
            'imageUrls' => [],
            'resolution' => '1K',
            'callBackUrl' => $callbackUrl,
            'aspectRatio' => '1:1',
        ], $options);

        $res = $this->request('POST', $this->baseUrl . '/generate-pro', $payload);
        $taskId = $res['data']['taskId'] ?? null;
        if (!is_string($taskId) || $taskId === '') {
            throw new \RuntimeException('nanobanana_missing_task_id');
        }
        return $taskId;
    }

    public function getRecordInfo(string $taskId): array
    {
        $url = $this->baseUrl . '/record-info?taskId=' . rawurlencode($taskId);
        return $this->request('GET', $url);
    }

    private function request(string $method, string $url, ?array $json = null): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('curl_init_failed');
        }

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
        ];

        if ($json !== null) {
            $body = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($body === false) {
                throw new \RuntimeException('json_encode_failed');
            }
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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
            throw new \RuntimeException('nanobanana_invalid_json');
        }

        if ($code < 200 || $code >= 300) {
            $msg = $decoded['msg'] ?? ($decoded['message'] ?? null);
            $msgStr = is_string($msg) && $msg !== '' ? $msg : ('http_' . $code);
            throw new \RuntimeException((string) $msgStr);
        }

        if (($decoded['code'] ?? 200) !== 200) {
            $msg = $decoded['msg'] ?? ($decoded['message'] ?? null);
            throw new \RuntimeException((string) ($msg ?? 'nanobanana_error'));
        }

        return $decoded;
    }
}
