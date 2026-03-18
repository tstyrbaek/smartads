<?php

namespace App\Services\LLM;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenAIClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $apiKey = (string) config('services.openai.api_key');
        $baseUrl = rtrim((string) config('services.openai.base_url'), '/');

        $this->http = Http::baseUrl($baseUrl)
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @return array<string, mixed>
     */
    public function chatCompletions(array $messages, array $opts = []): array
    {
        $model = (string) ($opts['model'] ?? config('services.openai.model'));
        $temperature = isset($opts['temperature']) ? (float) $opts['temperature'] : 0.2;
        $maxTokens = isset($opts['max_tokens']) ? (int) $opts['max_tokens'] : 600;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        $res = $this->http->post('/chat/completions', $payload);

        $status = $res->status();
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('openai_request_failed');
        }

        $body = $res->body();
        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new \RuntimeException('openai_invalid_response');
        }

        return $json;
    }
}
