<?php

namespace App\Services\Scraping\Sites;

use App\Services\Scraping\Scraper;
use App\Services\Scraping\SiteScraperInterface;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;

class HjemmehosSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'hjemmehos.dk') || str_contains($host, '.hjemmehos.dk'));
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
            $text = strip_tags($text);
            $text = preg_replace('/\r\n?|\x{00A0}/u', "\n", $text) ?? $text;
            $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
            $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
            $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
            $text = trim($text);

            return $text !== '' ? $text : null;
        };

        $decodeJson = static function (?string $json): ?array {
            if (!is_string($json) || trim($json) === '') {
                return null;
            }

            $decoded = json_decode(trim($json), true);
            return is_array($decoded) ? $decoded : null;
        };

        $extractPrice = static function ($value): ?string {
            if (!is_scalar($value)) {
                return null;
            }

            $stringValue = trim((string) $value);
            if ($stringValue === '') {
                return null;
            }

            if (is_numeric($value)) {
                $amount = (float) $value;
                $formatted = number_format($amount, 2, ',', '.');
                return $formatted . ' DKK';
            }

            return $stringValue;
        };

        $extractMoneyFromCents = static function ($value): ?string {
            if (!is_scalar($value) || !is_numeric((string) $value)) {
                return null;
            }

            $amount = ((float) $value) / 100;
            return number_format($amount, 2, ',', '.') . ' DKK';
        };

        $xpathLiteral = static function (string $value): string {
            if (!str_contains($value, "'")) {
                return "'" . $value . "'";
            }

            if (!str_contains($value, '"')) {
                return '"' . $value . '"';
            }

            $parts = explode("'", $value);
            $escaped = array_map(static fn (string $part): string => '"' . $part . '"', $parts);
            return 'concat(' . implode(", \"'\", ", $escaped) . ')';
        };

        $findSectionContentByHeading = static function (DOMXPath $xpath, string $heading) use ($xpathLiteral): ?DOMElement {
            $query = sprintf(
                '//button[contains(@class, "collapsible-trigger")][contains(normalize-space(.), %s)]/following-sibling::div[contains(@class, "collapsible-content")][1]//div[contains(@class, "collapsible-content__inner")][1]',
                $xpathLiteral($heading)
            );
            $nodes = $xpath->query($query);
            $node = $nodes instanceof DOMNodeList ? $nodes->item(0) : null;
            return $node instanceof DOMElement ? $node : null;
        };

        $extractSpecList = static function (?DOMElement $container): array {
            if (!$container instanceof DOMElement) {
                return [];
            }

            $items = [];
            $itemNodes = $container->getElementsByTagName('li');
            foreach ($itemNodes as $itemNode) {
                if (!$itemNode instanceof DOMElement || !str_contains($itemNode->getAttribute('class'), 'specs__item')) {
                    continue;
                }

                $spans = $itemNode->getElementsByTagName('span');
                $label = trim((string) $spans->item(0)?->textContent);
                $value = trim((string) $spans->item($spans->length - 1)?->textContent);
                if ($label === '' || $value === '') {
                    continue;
                }

                $items[$label] = $value;
            }

            return $items;
        };

        $productNode = $scraper->xpath($dom, '//*[@data-product][1]')->item(0);
        $productData = [];
        if ($productNode instanceof DOMElement) {
            $productData = [
                'product_id' => trim($productNode->getAttribute('data-product-id')),
                'variant_id' => trim($productNode->getAttribute('data-variant-id')),
                'product_code' => trim($productNode->getAttribute('data-cylindo-product-code')),
                'handle' => trim($productNode->getAttribute('data-product-handle')),
                'product_title' => trim($productNode->getAttribute('data-product-title')),
                'product_url' => trim($productNode->getAttribute('data-product-url')),
                'img_url' => trim($productNode->getAttribute('data-img-url')),
            ];
        }

        $ldJson = null;
        $ldJsonNodes = $scraper->xpath($dom, '//script[@type="application/ld+json"]');
        foreach ($ldJsonNodes as $node) {
            $decoded = $decodeJson($node->textContent);
            if (!is_array($decoded)) {
                continue;
            }
            if (($decoded['@type'] ?? null) === 'Product') {
                $ldJson = $decoded;
                break;
            }
        }

        $variantJson = $decodeJson($firstText($scraper->xpath($dom, '//textarea[@data-variant-json]')));
        $cylindoFeatures = $decodeJson($firstText($scraper->xpath($dom, '//script[@data-cylindo-features]')));
        $currentVariantJson = $decodeJson($firstText($scraper->xpath($dom, '//textarea[@data-current-variant-json]')));

        $title = $firstText($scraper->xpath($dom, '//h1[contains(@class, "product-single__title")]'));
        if ($title === '' && is_array($ldJson) && isset($ldJson['name']) && is_string($ldJson['name'])) {
            $title = trim($ldJson['name']);
        }
        if ($title === '' && isset($productData['product_title']) && is_string($productData['product_title']) && $productData['product_title'] !== '') {
            $title = $productData['product_title'];
        }
        if ($title === '') {
            $title = (string) ($scraper->title($dom) ?? '');
        }

        $brand = $firstText($scraper->xpath($dom, '//div[contains(@class, "product-single__vendor")]//a[1]'));
        if ($brand === '' && is_array($ldJson) && isset($ldJson['brand']) && is_string($ldJson['brand'])) {
            $brand = trim($ldJson['brand']);
        }

        $model = $firstText($scraper->xpath($dom, '//p[contains(@class, "linked-products__fabric__title")][contains(normalize-space(.), "Model:")]//span[contains(@class, "color-name")]'));
        $selectedColor = $firstText($scraper->xpath($dom, '//*[@data-linked-products-color-name][1]'));
        $selectedMaterial = $model;
        $price = $firstText($scraper->xpath($dom, '//*[@data-product-price][1]'));
        if ($price === '' && is_array($ldJson) && isset($ldJson['offers'][0]['price'])) {
            $price = (string) ($extractPrice($ldJson['offers'][0]['price']) ?? '');
        }

        $compareAtPrice = $firstText($scraper->xpath($dom, '//*[@data-compare-price][1]'));
        if ($compareAtPrice === '' && is_array($currentVariantJson) && isset($currentVariantJson['compare_at_price'])) {
            $compareAtPrice = (string) ($extractMoneyFromCents($currentVariantJson['compare_at_price']) ?? '');
        }

        $description = $normalizeText($firstText($scraper->xpath($dom, '//*[contains(@class, "product-description__text")][1]')));
        if ($description === null && is_array($ldJson) && isset($ldJson['description']) && is_string($ldJson['description'])) {
            $description = $normalizeText($ldJson['description']);
        }

        $deliveryText = $firstText($scraper->xpath($dom, '//*[contains(@class, "shipping-time")]//p[1]'));
        $freeShipping = $firstText($scraper->xpath($dom, '//*[@data-free-shipping][1]'));
        $shippingDetails = $normalizeText($firstText($scraper->xpath($dom, '//button[contains(@class, "collapsible-trigger")][contains(normalize-space(.), "Levering")]/following-sibling::div[contains(@class, "collapsible-content")][1]')));
        $warranty = $normalizeText($firstText($scraper->xpath($dom, '//button[contains(@class, "collapsible-trigger")][contains(normalize-space(.), "Garanti")]/following-sibling::div[contains(@class, "collapsible-content")][1]')));
        $storeInformation = $normalizeText($firstText($scraper->xpath($dom, '//button[contains(@class, "collapsible-trigger")][contains(normalize-space(.), "Butiksinformation")]/following-sibling::div[contains(@class, "collapsible-content")][1]')));
        $brandDescription = $normalizeText($firstText($scraper->xpath($dom, '//button[contains(@class, "collapsible-trigger")][contains(normalize-space(.), "Om ")]/following-sibling::div[contains(@class, "collapsible-content")][1]')));

        $measurements = $extractSpecList($findSectionContentByHeading($xpath, 'Produkt mål'));
        $specifications = $extractSpecList($findSectionContentByHeading($xpath, 'Specifikationer'));
        $similarSection = $extractSpecList($findSectionContentByHeading($xpath, 'Se lignende'));

        $categories = [];
        $breadcrumbNodes = $scraper->xpath($dom, '//nav[contains(@class, "breadcrumb")]//a[@href]');
        foreach ($breadcrumbNodes as $breadcrumbNode) {
            $value = trim((string) $breadcrumbNode->textContent);
            if ($value === '' || strcasecmp($value, 'Forside') === 0) {
                continue;
            }
            $categories[] = $value;
        }
        $categories = array_values(array_unique($categories));

        if (isset($similarSection['Kategori'])) {
            $categories[] = $similarSection['Kategori'];
        }
        if (isset($similarSection['Type'])) {
            $categories[] = $similarSection['Type'];
        }
        if (isset($similarSection['Underkategori'])) {
            $categories[] = $similarSection['Underkategori'];
        }
        $categories = array_values(array_unique(array_filter($categories, static fn ($value): bool => is_string($value) && $value !== '')));

        $series = $similarSection['Serie'] ?? null;
        $productType = $similarSection['Type'] ?? null;
        $subcategory = $similarSection['Underkategori'] ?? null;
        $material = $specifications['Materiale'] ?? null;
        $materialType = $specifications['Lædertype'] ?? $selectedMaterial;

        $images = [];
        if (is_array($ldJson) && isset($ldJson['image']['url']) && is_string($ldJson['image']['url'])) {
            $images[] = $resolveUrl($ldJson['image']['url'], $url);
        }
        if (isset($productData['img_url']) && is_string($productData['img_url']) && $productData['img_url'] !== '') {
            $images[] = $resolveUrl(str_replace('{width}', '1800', $productData['img_url']), $url);
        }

        $imageNodes = $scraper->xpath($dom, '//*[@data-product-images]//img[@data-photoswipe-src or @data-src or @src] | //a[@data-product-thumb][@href]');
        foreach ($imageNodes as $imageNode) {
            if (!$imageNode instanceof DOMElement) {
                continue;
            }
            $candidate = trim((string) ($imageNode->getAttribute('data-photoswipe-src') ?: $imageNode->getAttribute('data-src') ?: $imageNode->getAttribute('src') ?: $imageNode->getAttribute('href')));
            if ($candidate === '' || str_starts_with($candidate, 'data:')) {
                continue;
            }
            $images[] = $resolveUrl(str_replace('{width}', '1800', $candidate), $url);
        }
        $images = array_values(array_unique($images));

        $variants = [];
        if (is_array($variantJson)) {
            foreach ($variantJson as $variant) {
                if (!is_array($variant)) {
                    continue;
                }

                $variantImage = null;
                if (isset($variant['featured_image']['src']) && is_string($variant['featured_image']['src'])) {
                    $variantImage = $resolveUrl($variant['featured_image']['src'], $url);
                }

                $variants[] = [
                    'id' => isset($variant['id']) ? (string) $variant['id'] : null,
                    'title' => isset($variant['title']) && is_string($variant['title']) ? trim($variant['title']) : null,
                    'name' => isset($variant['name']) && is_string($variant['name']) ? trim($variant['name']) : null,
                    'options' => isset($variant['options']) && is_array($variant['options']) ? array_values(array_filter($variant['options'], static fn ($value): bool => is_string($value) && $value !== '')) : [],
                    'price' => $extractMoneyFromCents($variant['price'] ?? null),
                    'compare_at_price' => $extractMoneyFromCents($variant['compare_at_price'] ?? null),
                    'available' => isset($variant['available']) ? (bool) $variant['available'] : null,
                    'image' => $variantImage,
                ];
            }
        }

        $variantOptions = [];
        $optionFieldsets = $scraper->xpath($dom, '//fieldset[contains(@class, "variant-input-wrap")]');
        foreach ($optionFieldsets as $fieldset) {
            if (!$fieldset instanceof DOMElement) {
                continue;
            }

            $optionName = trim((string) ($fieldset->getAttribute('name') !== '' ? $fieldset->getAttribute('name') : $fieldset->getAttribute('data-handle')));
            if ($optionName === '') {
                continue;
            }

            $values = [];
            $valueNodes = $xpath->query('.//*[@data-value]', $fieldset);
            if ($valueNodes instanceof DOMNodeList) {
                foreach ($valueNodes as $valueNode) {
                    if (!$valueNode instanceof DOMElement) {
                        continue;
                    }
                    $value = trim($valueNode->getAttribute('data-value'));
                    if ($value !== '') {
                        $values[] = $value;
                    }
                }
            }

            $variantOptions[$optionName] = array_values(array_unique($values));
        }

        $featureMappings = [];
        if (is_array($cylindoFeatures)) {
            foreach ($cylindoFeatures as $featureSet) {
                if (!is_array($featureSet) || !isset($featureSet['id'])) {
                    continue;
                }
                $featureMappings[] = [
                    'id' => (string) $featureSet['id'],
                    'features' => isset($featureSet['features']) && is_array($featureSet['features'])
                        ? array_values(array_filter($featureSet['features'], static fn ($value): bool => is_string($value) && $value !== ''))
                        : [],
                ];
            }
        }

        $offers = [];
        if (is_array($ldJson) && isset($ldJson['offers']) && is_array($ldJson['offers'])) {
            foreach ($ldJson['offers'] as $offer) {
                if (!is_array($offer)) {
                    continue;
                }
                $offers[] = [
                    'price' => $extractPrice($offer['price'] ?? null),
                    'currency' => isset($offer['priceCurrency']) && is_string($offer['priceCurrency']) ? $offer['priceCurrency'] : null,
                    'availability' => isset($offer['availability']) && is_string($offer['availability']) ? $offer['availability'] : null,
                    'url' => isset($offer['url']) && is_string($offer['url']) ? $offer['url'] : null,
                    'valid_until' => isset($offer['priceValidUntil']) && is_string($offer['priceValidUntil']) ? $offer['priceValidUntil'] : null,
                ];
            }
        }

        $realtorUrl = is_array($ldJson) && isset($ldJson['url']) && is_string($ldJson['url']) ? $ldJson['url'] : $url;

        return [
            'url' => $url,
            'source' => 'hjemmehos',
            'title' => $title !== '' ? $title : null,
            'address' => null,
            'price' => $price !== '' ? $price : null,
            'compare_at_price' => $compareAtPrice !== '' ? $compareAtPrice : null,
            'brand' => $brand !== '' ? $brand : null,
            'model' => $selectedMaterial !== '' ? $selectedMaterial : null,
            'series' => is_string($series) && $series !== '' ? $series : null,
            'product_type' => is_string($productType) && $productType !== '' ? $productType : null,
            'subcategory' => is_string($subcategory) && $subcategory !== '' ? $subcategory : null,
            'categories' => $categories,
            'material' => is_string($material) && $material !== '' ? $material : null,
            'material_type' => is_string($materialType) && $materialType !== '' ? $materialType : null,
            'selected_color' => $selectedColor !== '' ? $selectedColor : null,
            'description' => $description,
            'brand_description' => $brandDescription,
            'delivery_time' => $deliveryText !== '' ? $deliveryText : null,
            'free_shipping' => $freeShipping !== '' ? $freeShipping : null,
            'shipping_details' => $shippingDetails,
            'warranty' => $warranty,
            'store_information' => $storeInformation,
            'dimensions' => $measurements,
            'specifications' => $specifications,
            'realtor_url' => $realtorUrl,
            'images' => $images,
            'variant_options' => $variantOptions,
            'variants' => $variants,
            'offers' => $offers,
            'feature_mappings' => $featureMappings,
            'product_id' => isset($productData['product_id']) && is_string($productData['product_id']) && $productData['product_id'] !== '' ? $productData['product_id'] : null,
            'current_variant_id' => isset($productData['variant_id']) && is_string($productData['variant_id']) && $productData['variant_id'] !== '' ? $productData['variant_id'] : null,
            'product_code' => isset($productData['product_code']) && is_string($productData['product_code']) && $productData['product_code'] !== '' ? $productData['product_code'] : null,
            'handle' => isset($productData['handle']) && is_string($productData['handle']) && $productData['handle'] !== '' ? $productData['handle'] : null,
        ];
    }
}
