<?php

namespace App\Services\Scraping\Sites;

use App\Services\Scraping\Scraper;
use App\Services\Scraping\SiteScraperInterface;
use DOMElement;
use DOMNodeList;
use DOMXPath;

class DanboligSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'danbolig.dk') || str_contains($host, '.danbolig.dk'));
    }

    public function scrape(string $url): array
    {
        $scraper = new Scraper();
        $html = $scraper->fetch($url);
        $dom = $scraper->loadDom($html);
        $xpath = new DOMXPath($dom);

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

        $title = $firstText($scraper->xpath($dom, '//h1'));
        if ($title === '') {
            $title = (string) ($scraper->title($dom) ?? '');
        }

        $address = $firstText($scraper->xpath($dom, '//*[contains(@class,"case__address")][1]'));
        $price = $firstText($scraper->xpath($dom, '//*[contains(@class,"case__price")][1]'));

        $desc = $normalizeText($firstText($scraper->xpath($dom, '//*[contains(@class,"case__description")][1]')));

        $facts = [];
        $factNodes = $xpath->query('//*[contains(@class,"casefacts") or contains(@class,"facts")]//li');
        if ($factNodes instanceof DOMNodeList) {
            foreach ($factNodes as $n) {
                if (!$n instanceof DOMElement) continue;
                $t = trim((string) $n->textContent);
                if ($t !== '') $facts[] = $t;
            }
        }
        $facts = array_values(array_unique($facts));

        $images = [];
        if (preg_match_all('~https?://[^"\'\s>]+\.(?:jpg|jpeg|png|webp)(?:\?[^"\'\s>]*)?~i', $html, $m) === 1 || !empty($m[0])) {
            foreach ($m[0] as $img) {
                $images[] = html_entity_decode(trim($img), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        $images = array_values(array_unique($images));

        return [
            'url' => $url,
            'source' => 'danbolig',
            'title' => $title !== '' ? $title : null,
            'address' => $address !== '' ? $address : null,
            'price' => $price !== '' ? $price : null,
            'build_year' => null,
            'description' => $desc,
            'realtor_url' => $url,
            'images' => $images,
            'equipment' => $facts,
        ];
    }
}
