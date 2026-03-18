<?php

namespace App\Services\Scraping\Sites;

use App\Services\Scraping\Scraper;
use App\Services\Scraping\SiteScraperInterface;
use DOMNodeList;

class BoligsidenSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'boligsiden.dk') || str_contains($host, '.boligsiden.dk'));
    }

    public function scrape(string $url): array
    {
        $scraper = new Scraper();
        $html = $scraper->fetch($url);
        $dom = $scraper->loadDom($html);

        $firstText = static function (DOMNodeList $nodes): string {
            return trim((string) $nodes->item(0)?->textContent);
        };

        $normalizeText = static function (?string $text): ?string {
            if (!is_string($text) || trim($text) === '') {
                return null;
            }

            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\r\n?|\x{00A0}/u', "\n", $text) ?? $text;
            $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
            $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
            $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
            $text = trim(strip_tags($text));

            return $text !== '' ? $text : null;
        };

        $title = (string) ($scraper->title($dom) ?? '');

        $address = $firstText($scraper->xpath($dom, '//*[@id="oversigt"]//h1//span[1]'));
        if ($address === '') {
            $address = $firstText($scraper->xpath($dom, '//*[@id="oversigt"]//h1'));
        }

        $price = $firstText($scraper->xpath($dom, '//*[@id="oversigt"]//h2[contains(normalize-space(.), "kr")]'));

        $description = $firstText($scraper->xpath($dom, '//*[@data-name="description"]//p'));
        if ($description !== '') {
            $description = preg_replace("/\n{3,}/", "\n\n", $description) ?? $description;
        }
        $description = $normalizeText($description);

        $resolveUrl = static function (string $src, string $baseUrl): string {
            if ($src === '' || preg_match('~^https?://~i', $src) === 1) {
                return $src;
            }

            $base = parse_url($baseUrl);
            if (!is_array($base) || !isset($base['scheme'], $base['host'])) {
                return $src;
            }

            $scheme = $base['scheme'];
            $host = $base['host'];
            $port = isset($base['port']) ? ':' . $base['port'] : '';

            if (str_starts_with($src, '//')) {
                return $scheme . ':' . $src;
            }

            if (str_starts_with($src, '/')) {
                return $scheme . '://' . $host . $port . $src;
            }

            $basePath = $base['path'] ?? '/';
            $dir = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
            $path = ($dir === '' ? '' : $dir) . '/' . $src;

            return $scheme . '://' . $host . $port . $path;
        };

        $imageNodes = $scraper->xpath(
            $dom,
            '//img[@src and not(starts-with(@src, "data:")) and (contains(@src, "images.boligsiden.dk") or contains(@src, "/images/case/"))]'
        );
        $images = [];
        foreach ($imageNodes as $img) {
            if (!$img instanceof \DOMElement) {
                continue;
            }
            $src = trim((string) $img->getAttribute('src'));
            if ($src === '') {
                continue;
            }
            $images[] = $resolveUrl($src, $url);
        }
        $images = array_values(array_unique($images));

        return [
            'url' => $url,
            'source' => 'boligsiden',
            'title' => $title !== '' ? $title : null,
            'address' => $address !== '' ? $address : null,
            'price' => $price !== '' ? $price : null,
            'build_year' => null,
            'description' => $description,
            'realtor_url' => $url,
            'images' => $images,
        ];
    }
}
