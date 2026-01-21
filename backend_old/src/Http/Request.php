<?php

declare(strict_types=1);

namespace SmartAdd\Http;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        public readonly ?string $rawBody,
        public readonly array $files,
        public readonly array $post,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        $rawBody = file_get_contents('php://input');
        if ($rawBody === false) {
            $rawBody = null;
        }

        return new self(
            $method,
            $path,
            $_GET,
            $headers,
            $rawBody,
            $_FILES,
            $_POST,
        );
    }

    public function header(string $name): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? null;
    }

    public function json(): ?array
    {
        if ($this->rawBody === null || $this->rawBody === '') {
            return null;
        }
        $decoded = json_decode($this->rawBody, true);
        return is_array($decoded) ? $decoded : null;
    }
}
