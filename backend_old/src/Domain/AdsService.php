<?php

declare(strict_types=1);

namespace SmartAdd\Domain;

use SmartAdd\Storage\JsonFile;

final class AdsService
{
    private JsonFile $file;

    public function __construct(private readonly string $storageDir)
    {
        $this->file = new JsonFile($this->storageDir . '/ads.json');
    }

    public function all(): array
    {
        $data = $this->file->readArray();
        return array_values(array_filter($data, 'is_array'));
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $ad) {
            if (($ad['id'] ?? null) === $id) {
                return $ad;
            }
        }
        return null;
    }

    public function upsert(array $ad): array
    {
        $ads = $this->all();
        $found = false;
        foreach ($ads as $i => $row) {
            if (($row['id'] ?? null) === ($ad['id'] ?? null)) {
                $ads[$i] = $ad;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $ads[] = $ad;
        }

        $this->file->writeArray($ads);
        return $ad;
    }

    public function create(string $text): array
    {
        $now = date(DATE_ATOM);
        $id = 'ad_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
        $ad = [
            'id' => $id,
            'text' => $text,
            'status' => 'creating',
            'nanobananaTaskId' => null,
            'resultImageUrl' => null,
            'localFilePath' => null,
            'error' => null,
            'createdAt' => $now,
            'updatedAt' => $now,
        ];

        return $this->upsert($ad);
    }
}
