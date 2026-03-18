<?php

namespace App\Services\Scraping;

use Illuminate\Support\Facades\Cache;

class ScrapeService
{
    public function __construct(
        private readonly UrlSafety $urlSafety,
        private readonly ScraperRegistry $registry,
    ) {
    }

    public function scrape(int $companyId, string $url): array
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('invalid_url');
        }

        $this->urlSafety->assertSafe($url);

        $cacheKey = 'scrape:' . $companyId . ':' . sha1($url);

        return Cache::store('file')->remember($cacheKey, now()->addMinutes(10), function () use ($url) {
            $scraper = $this->registry->forUrl($url);
            if (!$scraper) {
                throw new \InvalidArgumentException('unsupported_domain');
            }

            $raw = $scraper->scrape($url);
            if (!is_array($raw)) {
                throw new \RuntimeException('parse_failed');
            }

            $images = [];
            $rawImages = $raw['images'] ?? [];
            if (is_array($rawImages)) {
                $count = 0;
                foreach ($rawImages as $imgUrl) {
                    if ($count >= 10) break;
                    if (!is_string($imgUrl)) continue;
                    $imgUrl = trim($imgUrl);
                    if ($imgUrl === '') continue;
                    $images[] = ['url' => $imgUrl];
                    $count++;
                }
            }

            $fullText = $this->buildFullText($raw);

            $structured = $raw;
            unset($structured['images']);

            return [
                'url' => $url,
                'source' => is_string($raw['source'] ?? null) ? $raw['source'] : null,
                'title' => is_string($raw['title'] ?? null) ? $raw['title'] : null,
                'description' => is_string($raw['description'] ?? null) ? $raw['description'] : null,
                'structured' => $structured,
                'images' => $images,
                'full_text' => $fullText,
            ];
        });
    }

    private function buildFullText(array $raw): string
    {
        $parts = [];

        $title = isset($raw['title']) && is_string($raw['title']) ? trim($raw['title']) : '';
        if ($title !== '') {
            $parts[] = $title;
        }

        $price = isset($raw['price']) && is_string($raw['price']) ? trim($raw['price']) : '';
        if ($price !== '') {
            $parts[] = 'Pris: ' . $price;
        }

        $desc = isset($raw['description']) && is_string($raw['description']) ? trim($raw['description']) : '';
        if ($desc !== '') {
            $parts[] = '';
            $parts[] = 'BESKRIVELSE:';
            $parts[] = $desc;
        }

        $equipment = $raw['equipment'] ?? null;
        if (is_array($equipment) && count($equipment) > 0) {
            $lines = [];
            foreach ($equipment as $e) {
                if (!is_string($e)) continue;
                $e = trim($e);
                if ($e !== '') $lines[] = $e;
            }
            if (count($lines) > 0) {
                $parts[] = '';
                $parts[] = 'UDSTYR:';
                $parts[] = implode("\n", $lines);
            }
        }

        $spec = $raw['specifications'] ?? null;
        if (is_array($spec)) {
            $flat = [];
            foreach ($spec as $group) {
                if (!is_array($group)) continue;
                foreach ($group as $k => $v) {
                    if (!is_string($k)) continue;
                    if (!is_string($v)) continue;
                    $k = trim($k);
                    $v = trim($v);
                    if ($k === '' || $v === '') continue;
                    $flat[] = $k . ': ' . $v;
                }
            }
            if (count($flat) > 0) {
                $parts[] = '';
                $parts[] = 'FAKTA:';
                $parts[] = implode("\n", array_slice($flat, 0, 40));
            }
        }

        $text = trim(implode("\n", $parts));
        return $text;
    }
}
