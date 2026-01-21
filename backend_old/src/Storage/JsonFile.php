<?php

declare(strict_types=1);

namespace SmartAdd\Storage;

final class JsonFile
{
    public function __construct(private readonly string $absolutePath)
    {
    }

    public function readArray(): array
    {
        if (!is_file($this->absolutePath)) {
            return [];
        }

        $raw = file_get_contents($this->absolutePath);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function writeArray(array $data): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('json_encode_failed');
        }

        if (file_put_contents($this->absolutePath, $json) === false) {
            throw new \RuntimeException('file_write_failed');
        }
    }
}
