<?php

declare(strict_types=1);

namespace SmartAdd\Domain;

use SmartAdd\Storage\JsonFile;

final class BrandService
{
    private JsonFile $file;

    public function __construct(private readonly string $storageDir)
    {
        $this->file = new JsonFile($this->storageDir . '/brand.json');
    }

    public function get(): array
    {
        return $this->file->readArray();
    }

    public function save(array $brand): array
    {
        $brand['updatedAt'] = date(DATE_ATOM);
        $this->file->writeArray($brand);
        return $brand;
    }
}
