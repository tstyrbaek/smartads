<?php

namespace App\Services;

class AdSizeService
{
    public function allowedSizes(): array
    {
        $sizes = config('smartads.allowed_ad_sizes', []);
        if (!is_array($sizes)) {
            return [];
        }

        $out = [];
        foreach ($sizes as $item) {
            if (!is_array($item)) {
                continue;
            }

            $w = $item['width'] ?? null;
            $h = $item['height'] ?? null;
            if (!is_numeric($w) || !is_numeric($h)) {
                continue;
            }

            $w = (int) $w;
            $h = (int) $h;
            if ($w <= 0 || $h <= 0) {
                continue;
            }

            $out[] = ['width' => $w, 'height' => $h];
        }

        return $out;
    }

    public function isAllowed(?int $width, ?int $height): bool
    {
        if (!$width || !$height) {
            return false;
        }

        foreach ($this->allowedSizes() as $size) {
            if ((int) $size['width'] === (int) $width && (int) $size['height'] === (int) $height) {
                return true;
            }
        }

        return false;
    }
}
