<?php

declare(strict_types=1);

final class ElsalgSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'elsalg.dk') || str_contains($host, '.elsalg.dk'));
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

        $extractTableData = static function (DOMXPath $xpath, DOMDocument $dom): array {
            $tables = $xpath->query('//table[contains(@class, "specification-table")]');
            $result = [];
            if (!$tables instanceof DOMNodeList) {
                return $result;
            }

            foreach ($tables as $table) {
                if (!$table instanceof DOMElement) {
                    continue;
                }

                $section = 'General';
                $rows = $xpath->query('.//tr', $table);
                if (!$rows instanceof DOMNodeList) {
                    continue;
                }

                foreach ($rows as $row) {
                    if (!$row instanceof DOMElement) {
                        continue;
                    }

                    $className = $row->getAttribute('class');
                    if (str_contains($className, 'table-heading')) {
                        $heading = trim((string) $row->textContent);
                        if ($heading !== '') {
                            $section = $heading;
                        }
                        if (!isset($result[$section])) {
                            $result[$section] = [];
                        }
                        continue;
                    }

                    $headers = $row->getElementsByTagName('th');
                    $cells = $row->getElementsByTagName('td');
                    $label = trim((string) $headers->item(0)?->textContent);
                    $value = trim((string) $cells->item(0)?->textContent);
                    if ($label === '' || $value === '') {
                        continue;
                    }

                    if (!isset($result[$section])) {
                        $result[$section] = [];
                    }
                    $result[$section][$label] = $value;
                }
            }

            return $result;
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

        $title = $firstText($scraper->xpath($dom, '//h1[contains(@class, "page-title")]//span[contains(@class, "base")]'));
        if ($title === '') {
            $title = (string) ($scraper->title($dom) ?? '');
        }

        $sku = $firstText($scraper->xpath($dom, '//div[contains(@class, "sku")][1]'));
        $gtin = $firstText($scraper->xpath($dom, '//div[contains(@class, "videoly-product-gtin")][1]'));
        $price = $firstText($scraper->xpath($dom, '//*[@data-price-type="finalPrice"]//span[contains(@class, "price")][1]'));
        $deliveryTime = $firstText($scraper->xpath($dom, '//div[contains(@class, "product-delivery-wrapper")]//p[contains(@class, "delivery-text")][1]'));
        $energyLabelUrl = null;
        $energyLink = $scraper->xpath($dom, '//div[contains(@class, "energy-label-text")]//a[@href][1]')->item(0);
        if ($energyLink instanceof DOMElement) {
            $href = trim($energyLink->getAttribute('href'));
            if ($href !== '') {
                $energyLabelUrl = $resolveUrl($href, $url);
            }
        }

        $overviewNode = $scraper->xpath($dom, '//div[contains(@class, "product attribute overview")]')->item(0);
        $overviewText = $normalizeText($overviewNode instanceof DOMNode ? $overviewNode->textContent : null);
        $overviewItems = $extractListItems($xpath, $overviewNode);

        $descriptionNode = $scraper->xpath($dom, '//*[@id="product.description.custom"]//div[contains(@class, "product attribute description")]//div[contains(@class, "value")][1]')->item(0);
        $descriptionTitle = $firstText($scraper->xpath($dom, '//*[@id="product.description.custom"]//h2[1]'));
        $description = $normalizeText($descriptionNode instanceof DOMNode ? $descriptionNode->textContent : null);
        if ($descriptionTitle !== '' && $description !== null && !str_starts_with($description, $descriptionTitle)) {
            $description = $descriptionTitle . "\n\n" . $description;
        }
        $descriptionItems = $extractListItems($xpath, $descriptionNode);

        $specifications = $extractTableData($xpath, $dom);
        $generalSpecifications = $specifications['Generelt'] ?? [];
        $brand = is_string($generalSpecifications['Mærke'] ?? null) ? $generalSpecifications['Mærke'] : null;
        $productType = is_string($generalSpecifications['Produkttype'] ?? null) ? $generalSpecifications['Produkttype'] : null;
        $color = is_string($generalSpecifications['Farve'] ?? null) ? $generalSpecifications['Farve'] : null;
        $serviceSupport = is_string($generalSpecifications['Service & Support'] ?? null) ? $generalSpecifications['Service & Support'] : null;

        $symbols = [];
        $symbolNodes = $scraper->xpath($dom, '//div[contains(@class, "product-symbols")]//a[contains(@class, "product-symbol")]');
        foreach ($symbolNodes as $symbolNode) {
            if (!$symbolNode instanceof DOMElement) {
                continue;
            }

            $img = $xpath->query('.//img[@src]', $symbolNode)?->item(0);
            $image = null;
            $alt = null;
            if ($img instanceof DOMElement) {
                $image = $resolveUrl(trim($img->getAttribute('src')), $url);
                $alt = trim($img->getAttribute('alt'));
            }

            $label = trim((string) ($xpath->query('.//*[contains(@class, "label-info-text")][1]', $symbolNode)?->item(0)?->textContent ?? ''));
            $symbols[] = [
                'label' => $label !== '' ? $label : ($alt !== '' ? $alt : null),
                'image' => $image,
            ];
        }

        $images = [];
        $mainImage = $scraper->xpath($dom, '//img[contains(@class, "main-image")][@src][1]')->item(0);
        if ($mainImage instanceof DOMElement) {
            $src = trim($mainImage->getAttribute('src'));
            if ($src !== '') {
                $images[] = $resolveUrl($src, $url);
            }
        }

        $thumbnailNodes = $scraper->xpath($dom, '//a[contains(@class, "thumb-image")][@href]');
        foreach ($thumbnailNodes as $thumbnailNode) {
            if (!$thumbnailNode instanceof DOMElement) {
                continue;
            }
            $href = trim($thumbnailNode->getAttribute('href'));
            if ($href !== '') {
                $images[] = $resolveUrl($href, $url);
            }
        }
        $images = array_values(array_unique($images));

        return [
            'url' => $url,
            'source' => 'elsalg',
            'title' => $title !== '' ? $title : null,
            'address' => null,
            'price' => $price !== '' ? $price : null,
            'brand' => $brand,
            'product_type' => $productType,
            'sku' => $sku !== '' ? $sku : null,
            'gtin' => $gtin !== '' ? $gtin : null,
            'color' => $color,
            'delivery_time' => $deliveryTime !== '' ? $deliveryTime : null,
            'energy_label_url' => $energyLabelUrl,
            'overview' => $overviewText,
            'overview_items' => $overviewItems,
            'description' => $description,
            'description_items' => $descriptionItems,
            'service_support' => $serviceSupport,
            'symbols' => $symbols,
            'specifications' => $specifications,
            'realtor_url' => $url,
            'images' => $images,
        ];
    }
}
