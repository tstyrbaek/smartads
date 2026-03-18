<?php

namespace App\Services\Scraping\Sites;

use App\Services\Scraping\Scraper;
use App\Services\Scraping\SiteScraperInterface;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;

class HuniqueSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'hunique.dk') || str_contains($host, '.hunique.dk'));
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

        $normalizeText = static function (?string $text): ?string {
            if (!is_string($text) || trim($text) === '') {
                return null;
            }

            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\r\n?/u', "\n", $text) ?? $text;
            $text = str_replace("\xc2\xa0", ' ', $text);
            $text = strip_tags($text);
            $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
            $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
            $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
            $text = trim($text);

            return $text !== '' ? $text : null;
        };

        $extractListItems = static function (DOMXPath $xpath, ?DOMNode $contextNode): array {
            if (!$contextNode instanceof DOMNode) {
                return [];
            }

            $items = [];
            $nodes = $xpath->query('.//li', $contextNode);
            if (!$nodes instanceof DOMNodeList) {
                return $items;
            }

            foreach ($nodes as $node) {
                $value = trim((string) $node->textContent);
                if ($value !== '') {
                    $items[] = $value;
                }
            }

            return array_values(array_unique($items));
        };

        $title = $firstText($scraper->xpath($dom, '//h1//*[contains(@class, "field--name-title")][1] | //h1[1]'));
        if ($title === '') {
            $title = (string) ($scraper->title($dom) ?? '');
        }

        $price = $firstText($scraper->xpath($dom, '//*[contains(@class, "field--name-price")]//*[contains(@class, "new-price")][1] | //*[contains(@class, "field--name-price")]//*[contains(@class, "field__item")][1]'));
        $oldPrice = $firstText($scraper->xpath($dom, '//*[contains(@class, "field--name-price")]//*[contains(@class, "old-price")][1] | //*[contains(@class, "field--name-price")]//*[contains(@class, "list-price")][1]'));
        $ribbon = $firstText($scraper->xpath($dom, '//*[contains(@class, "ribbon")]//span[1] | //*[contains(@class, "ribbon")][1]'));

        $descriptionNode = $scraper->xpath($dom, '//*[contains(@class, "field--name-body")][1]')->item(0);
        $description = $normalizeText($descriptionNode instanceof DOMNode ? $descriptionNode->textContent : null);
        $descriptionItems = $extractListItems($xpath, $descriptionNode);

        $images = [];
        $imageNodes = $scraper->xpath($dom, '//*[contains(@class, "field--name-field-produktfotos")]//img[@src] | //figure[contains(@class, "imghover")]//a[@href]');
        foreach ($imageNodes as $imageNode) {
            if (!$imageNode instanceof DOMElement) {
                continue;
            }

            $candidate = '';
            if ($imageNode->hasAttribute('href')) {
                $candidate = trim($imageNode->getAttribute('href'));
            } elseif ($imageNode->hasAttribute('src')) {
                $candidate = trim($imageNode->getAttribute('src'));
            }

            if ($candidate === '' || str_starts_with($candidate, 'data:')) {
                continue;
            }

            $images[] = $resolveUrl($candidate, $url);
        }
        $images = array_values(array_unique($images));

        $stockText = $firstText($scraper->xpath($dom, '//*[contains(@class, "product--variation-field--variation_field_leveringsstatus")]//*[contains(@class, "field__item")][1] | //*[contains(@class, "product--variation-field--variation_field_leveringsstatus")][1]'));
        if ($stockText !== '') {
            $stockText = $normalizeText($stockText) ?? '';
        }

        $buttonText = $firstText($scraper->xpath($dom, '//button[contains(@class, "button--add-to-cart")][1]'));
        $inStock = null;
        if ($buttonText !== '') {
            $inStock = stripos($buttonText, 'tilføj til indkøbskurv') !== false;
        }

        $quantityMin = null;
        $quantityNode = $scraper->xpath($dom, '//input[@type="number" and contains(@name, "quantity")][1]')->item(0);
        if ($quantityNode instanceof DOMElement) {
            $min = trim($quantityNode->getAttribute('min'));
            if ($min !== '' && is_numeric($min)) {
                $quantityMin = (int) $min;
            }
        }

        $breadcrumbs = [];
        $breadcrumbNodes = $scraper->xpath($dom, '//nav[contains(@class, "breadcrumb")]//a | //nav[contains(@class, "breadcrumb")]//span | //*[contains(@class, "breadcrumb")]//a | //*[contains(@class, "breadcrumb")]//span');
        foreach ($breadcrumbNodes as $breadcrumbNode) {
            $value = trim((string) $breadcrumbNode->textContent);
            if ($value !== '') {
                $breadcrumbs[] = $value;
            }
        }
        $breadcrumbs = array_values(array_unique($breadcrumbs));

        $canonicalUrl = null;
        $canonicalNode = $scraper->xpath($dom, '//link[@rel="canonical"][@href][1]')->item(0);
        if ($canonicalNode instanceof DOMElement) {
            $canonicalHref = trim($canonicalNode->getAttribute('href'));
            if ($canonicalHref !== '') {
                $canonicalUrl = $resolveUrl($canonicalHref, $url);
            }
        }

        $sharedUrl = null;
        $sharedUrlNode = $scraper->xpath($dom, '//*[@data-a2a-url][1]')->item(0);
        if ($sharedUrlNode instanceof DOMElement) {
            $shared = trim($sharedUrlNode->getAttribute('data-a2a-url'));
            if ($shared !== '') {
                $sharedUrl = $resolveUrl($shared, $url);
            }
        }

        $variationTitle = $firstText($scraper->xpath($dom, '//*[contains(@class, "product--variation-field--variation_title")][1]'));
        $variants = [];
        if ($variationTitle !== '') {
            $variants[] = [
                'title' => $variationTitle,
                'price' => $price !== '' ? $price : null,
                'in_stock' => $inStock,
            ];
        }

        $viabillPrice = null;
        $viabillNode = $scraper->xpath($dom, '//*[contains(@class, "viabill-pricetag")][1]')->item(0);
        if ($viabillNode instanceof DOMElement) {
            $resolved = trim($viabillNode->getAttribute('data-resolved-price'));
            if ($resolved !== '' && is_numeric($resolved)) {
                $viabillPrice = number_format((float) $resolved, 2, ',', '.') . ' DKK';
            }
        }

        $pageText = html_entity_decode(trim((string) $dom->textContent), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $brand = null;
        if (preg_match('/^([A-ZÆØÅ][\p{L}\d\-]+)\s+/u', $title, $matches) === 1) {
            $brand = trim($matches[1]);
        }

        $productType = null;
        $path = (string) parse_url($sharedUrl ?? $canonicalUrl ?? $url, PHP_URL_PATH);
        if (preg_match('~/([^/?#]+)/[^/?#]+/?$~', $path, $matches) === 1) {
            $productType = trim(str_replace(['-', '_'], ' ', $matches[1]));
        }

        $dogKeywords = [];
        foreach (['hund', 'hunde', 'dog', 'legetøj', 'tennisbold', 'bold'] as $keyword) {
            if (stripos($pageText, $keyword) !== false) {
                $dogKeywords[] = $keyword;
            }
        }

        return [
            'url' => $url,
            'source' => 'hunique',
            'title' => $title !== '' ? $title : null,
            'address' => null,
            'price' => $price !== '' ? $price : null,
            'regular_price' => $oldPrice !== '' ? $oldPrice : null,
            'label' => $ribbon !== '' ? $ribbon : null,
            'brand' => $brand,
            'product_type' => $productType !== '' ? $productType : null,
            'description' => $description,
            'description_items' => $descriptionItems,
            'delivery_info' => $stockText !== '' ? $stockText : null,
            'stock_status' => $stockText !== '' ? $stockText : null,
            'in_stock' => $inStock,
            'minimum_quantity' => $quantityMin,
            'images' => $images,
            'variants' => $variants,
            'breadcrumbs' => $breadcrumbs,
            'category' => $breadcrumbs[1] ?? $productType,
            'subcategory' => $breadcrumbs[2] ?? null,
            'share_url' => $sharedUrl,
            'realtor_url' => $canonicalUrl ?? $sharedUrl ?? $url,
            'financing_price' => $viabillPrice,
            'keywords' => array_values(array_unique($dogKeywords)),
        ];
    }
}
