<?php

namespace App\Services\Scraping\Sites;

use App\Services\Scraping\Scraper;
use App\Services\Scraping\SiteScraperInterface;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;

class CykelcenterMidtjyllandSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'cykelcentermidtjylland.dk') || str_contains($host, '.cykelcentermidtjylland.dk'));
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

        $extractListText = static function (DOMXPath $xpath, ?DOMNode $contextNode): array {
            if (!$contextNode instanceof DOMNode) {
                return [];
            }

            $values = [];
            $nodes = $xpath->query('.//li', $contextNode);
            if (!$nodes instanceof DOMNodeList) {
                return $values;
            }

            foreach ($nodes as $node) {
                $value = trim((string) $node->textContent);
                if ($value !== '') {
                    $values[] = $value;
                }
            }

            return array_values(array_unique($values));
        };

        $decodeHtmlEntities = static function (string $value): string {
            return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        };

        $decodeVariationJson = static function (?string $json) use ($decodeHtmlEntities): ?array {
            if (!is_string($json) || trim($json) === '') {
                return null;
            }

            $decodedString = $decodeHtmlEntities($json);
            $decoded = json_decode($decodedString, true);
            return is_array($decoded) ? $decoded : null;
        };

        $title = $firstText($scraper->xpath($dom, '//h1[contains(@class, "product_title")]'));
        if ($title === '') {
            $title = (string) ($scraper->title($dom) ?? '');
        }

        $currentPrice = $firstText($scraper->xpath($dom, '//p[contains(@class, "price")]//ins//span[contains(@class, "amount")][1] | //p[contains(@class, "price")]//span[contains(@class, "amount")][1]'));
        $regularPrice = $firstText($scraper->xpath($dom, '//p[contains(@class, "price")]//del//span[contains(@class, "amount")][1]'));
        $saleBadge = $firstText($scraper->xpath($dom, '//*[contains(@class, "onsale")][1]'));
        $sku = $firstText($scraper->xpath($dom, '//*[contains(@class, "sku_wrapper")]//*[contains(@class, "sku")][1]'));
        if ($sku === 'N/A') {
            $sku = '';
        }

        $shortDescriptionNode = $scraper->xpath($dom, '//*[contains(@class, "woocommerce-product-details__short-description")][1]')->item(0);
        $shortDescription = $normalizeText($shortDescriptionNode instanceof DOMNode ? $shortDescriptionNode->textContent : null);
        $shortDescriptionItems = $extractListText($xpath, $shortDescriptionNode);

        $accordionDescriptionNode = $scraper->xpath($dom, '//*[@id="jet-toggle-content-2251"]//*[contains(@class, "jet-toggle__content-inner")][1]')->item(0);
        $description = $normalizeText($accordionDescriptionNode instanceof DOMNode ? $accordionDescriptionNode->textContent : null);

        $specificationNode = $scraper->xpath($dom, '//*[contains(@class, "jet-toggle__label-text")][normalize-space(.)="Specifikationer"]/ancestor::div[contains(@class, "jet-accordion__item")][1]//*[contains(@class, "jet-toggle__content-inner")][1]')->item(0);
        $specificationText = $normalizeText($specificationNode instanceof DOMNode ? $specificationNode->textContent : null);
        $specificationItems = $extractListText($xpath, $specificationNode);
        $specifications = [];
        foreach ($specificationItems as $item) {
            if (preg_match('/^([^:]+):\s*(.+)$/u', $item, $m) === 1) {
                $specifications[trim($m[1])] = trim($m[2]);
            }
        }

        $deliveryTexts = [];
        $deliveryNodes = $scraper->xpath($dom, '//*[contains(@class, "on-stock") or contains(@class, "fjernlager-badge") or contains(@class, "not-on-stock")]//p');
        foreach ($deliveryNodes as $deliveryNode) {
            $value = trim((string) $deliveryNode->textContent);
            if ($value !== '') {
                $deliveryTexts[] = $value;
            }
        }
        $deliveryTexts = array_values(array_unique($deliveryTexts));
        $deliveryInfo = $deliveryTexts !== [] ? implode("\n", $deliveryTexts) : null;

        $images = [];
        $imageNodes = $scraper->xpath($dom, '//div[contains(@class, "woocommerce-product-gallery")]//figure[contains(@class, "woocommerce-product-gallery__image")]');
        foreach ($imageNodes as $imageNode) {
            if (!$imageNode instanceof DOMElement) {
                continue;
            }
            $href = trim((string) ($xpath->query('.//a[@href][1]', $imageNode)?->item(0)?->attributes?->getNamedItem('href')?->nodeValue ?? ''));
            $large = trim((string) ($xpath->query('.//img[@data-large_image][1]', $imageNode)?->item(0)?->attributes?->getNamedItem('data-large_image')?->nodeValue ?? ''));
            $src = trim((string) ($xpath->query('.//img[@src][1]', $imageNode)?->item(0)?->attributes?->getNamedItem('src')?->nodeValue ?? ''));
            foreach ([$large, $href, $src] as $candidate) {
                if ($candidate !== '') {
                    $images[] = $candidate;
                }
            }
        }

        $variationForm = $scraper->xpath($dom, '//form[contains(@class, "variations_form")][1]')->item(0);
        $variationData = [];
        if ($variationForm instanceof DOMElement) {
            $variationData = $decodeVariationJson($variationForm->getAttribute('data-product_variations')) ?? [];
        }

        $variantOptions = [];
        $selectNodes = $scraper->xpath($dom, '//form[contains(@class, "variations_form")]//select[@name]');
        foreach ($selectNodes as $selectNode) {
            if (!$selectNode instanceof DOMElement) {
                continue;
            }
            $name = trim($selectNode->getAttribute('name'));
            if ($name === '') {
                continue;
            }
            $values = [];
            $options = $xpath->query('./option[@value != ""]', $selectNode);
            if ($options instanceof DOMNodeList) {
                foreach ($options as $option) {
                    $value = trim((string) $option->textContent);
                    if ($value !== '') {
                        $values[] = $value;
                    }
                }
            }
            $variantOptions[$name] = array_values(array_unique($values));
        }

        $variants = [];
        foreach ($variationData as $variation) {
            if (!is_array($variation)) {
                continue;
            }

            $attributes = [];
            foreach (($variation['attributes'] ?? []) as $key => $value) {
                if (!is_string($key) || !is_string($value) || $value === '') {
                    continue;
                }
                $attributes[$key] = $value;
            }

            $variantImages = [];
            if (isset($variation['image']) && is_array($variation['image'])) {
                foreach (['full_src', 'url', 'src', 'gallery_thumbnail_src', 'thumb_src'] as $imageKey) {
                    $candidate = trim((string) ($variation['image'][$imageKey] ?? ''));
                    if ($candidate !== '') {
                        $variantImages[] = $candidate;
                    }
                }
            }
            if (isset($variation['additional_variation_images_default']) && is_array($variation['additional_variation_images_default'])) {
                foreach ($variation['additional_variation_images_default'] as $image) {
                    if (!is_array($image)) {
                        continue;
                    }
                    foreach (['full_src', 'src', 'thumbnail_src', 'data_large_image', 'data_src'] as $imageKey) {
                        $candidate = trim((string) ($image[$imageKey] ?? ''));
                        if ($candidate !== '') {
                            $variantImages[] = $candidate;
                        }
                    }
                }
            }
            $variantImages = array_values(array_unique($variantImages));
            $images = array_merge($images, $variantImages);

            $variants[] = [
                'variation_id' => isset($variation['variation_id']) ? (int) $variation['variation_id'] : null,
                'display_name' => isset($variation['display_name']) ? trim((string) $variation['display_name']) : null,
                'sku' => isset($variation['sku']) && trim((string) $variation['sku']) !== '' ? trim((string) $variation['sku']) : null,
                'price' => isset($variation['display_price']) ? 'kr.' . number_format((float) $variation['display_price'], 2, ',', '.') : null,
                'regular_price' => isset($variation['display_regular_price']) ? 'kr.' . number_format((float) $variation['display_regular_price'], 2, ',', '.') : null,
                'in_stock' => isset($variation['is_in_stock']) ? (bool) $variation['is_in_stock'] : null,
                'attributes' => $attributes,
                'images' => $variantImages,
                'fjernlager' => isset($variation['fjernlager']) ? (bool) $variation['fjernlager'] : null,
            ];
        }
        $images = array_values(array_unique($images));

        $breadcrumbs = [];
        $breadcrumbNodes = $scraper->xpath($dom, '//nav[contains(@class, "wd-breadcrumbs")]//a | //nav[contains(@class, "wd-breadcrumbs")]//span[contains(@class, "wd-last")]');
        foreach ($breadcrumbNodes as $breadcrumbNode) {
            $value = trim((string) $breadcrumbNode->textContent);
            if ($value !== '') {
                $breadcrumbs[] = $value;
            }
        }
        $breadcrumbs = array_values(array_unique($breadcrumbs));

        $contactPhone = null;
        $phoneNode = $scraper->xpath($dom, '//a[starts-with(@href, "tel:")][1]')->item(0);
        if ($phoneNode instanceof DOMElement) {
            $contactPhone = trim((string) $phoneNode->textContent);
        }

        $contactEmail = null;
        $emailNode = $scraper->xpath($dom, '//a[starts-with(@href, "mailto:")][1]')->item(0);
        if ($emailNode instanceof DOMElement) {
            $href = trim($emailNode->getAttribute('href'));
            $contactEmail = str_starts_with($href, 'mailto:') ? substr($href, 7) : null;
        }

        $sparxpresPrice = $firstText($scraper->xpath($dom, '//*[@id="sparxpres-formatted-monthly-payments"][1]'));
        $complianceText = $normalizeText($firstText($scraper->xpath($dom, '//*[@id="sparxpres-compliance-text"][1]')));

        return [
            'url' => $url,
            'source' => 'cykelcentermidtjylland',
            'title' => $title !== '' ? $title : null,
            'address' => null,
            'price' => $currentPrice !== '' ? $currentPrice : null,
            'regular_price' => $regularPrice !== '' ? $regularPrice : null,
            'sale_badge' => $saleBadge !== '' ? $saleBadge : null,
            'sku' => $sku !== '' ? $sku : null,
            'description' => $description ?? $shortDescription,
            'short_description' => $shortDescription,
            'short_description_items' => $shortDescriptionItems,
            'specification_text' => $specificationText,
            'specifications' => $specifications,
            'specification_items' => $specificationItems,
            'delivery_info' => $deliveryInfo,
            'realtor_url' => $url,
            'images' => $images,
            'variant_options' => $variantOptions,
            'variants' => $variants,
            'breadcrumbs' => $breadcrumbs,
            'category' => $breadcrumbs[1] ?? null,
            'subcategory' => $breadcrumbs[2] ?? null,
            'contact_phone' => $contactPhone,
            'contact_email' => $contactEmail,
            'monthly_finance_price' => $sparxpresPrice !== '' ? $sparxpresPrice . ' kr. pr. måned' : null,
            'finance_details' => $complianceText,
        ];
    }
}
