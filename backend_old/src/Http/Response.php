<?php

declare(strict_types=1);

namespace SmartAdd\Http;

final class Response
{
    public function __construct(
        private readonly int $status,
        private readonly array $headers,
        private readonly string $body,
    ) {
    }

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            $payload = '{"error":"json_encode_failed"}';
            $status = 500;
        }

        return new self($status, array_merge([
            'content-type' => 'application/json; charset=utf-8',
        ], $headers), $payload);
    }

    public static function text(string $text, int $status = 200, array $headers = []): self
    {
        return new self($status, array_merge([
            'content-type' => 'text/plain; charset=utf-8',
        ], $headers), $text);
    }

    public static function file(string $absolutePath, string $downloadName, string $contentType = 'application/octet-stream', array $headers = []): self
    {
        if (!is_file($absolutePath)) {
            return self::json(['error' => 'file_not_found'], 404);
        }

        $body = file_get_contents($absolutePath);
        if ($body === false) {
            return self::json(['error' => 'file_read_failed'], 500);
        }

        return new self(200, array_merge([
            'content-type' => $contentType,
            'content-disposition' => 'attachment; filename="' . addslashes($downloadName) . '"',
        ], $headers), $body);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
