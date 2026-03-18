<?php

namespace App\Services\Scraping\Sites;

use App\Services\Scraping\Scraper;
use App\Services\Scraping\SiteScraperInterface;
use DOMElement;
use DOMNodeList;
use DOMXPath;

class BmcLeasingSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'bmcleasing.dk') || str_contains($host, '.bmcleasing.dk'));
    }

    public function scrape(string $url): array
    {
        $scraper = new Scraper();
        $html = $scraper->fetch($url);
        $dom = $scraper->loadDom($html);

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

        $extractLabeledValue = static function (DOMXPath $xpath, string $label) use ($xpathLiteral): ?string {
            $query = sprintf(
                '//ul[contains(@class, "car-data-ul")]//li[.//div[contains(@class, "el-title")][contains(normalize-space(.), %s)]]//div[contains(@class, "el-content")]',
                $xpathLiteral($label)
            );
            $nodes = $xpath->query($query);
            if (!$nodes instanceof DOMNodeList) {
                return null;
            }
            $value = trim((string) $nodes->item(0)?->textContent);
            return $value !== '' ? $value : null;
        };

        $extractFloatString = static function (?string $value): ?string {
            if (!is_string($value) || $value === '') {
                return null;
            }
            if (preg_match('/(\d+(?:[\.,]\d+)?)/', $value, $m) !== 1) {
                return null;
            }
            return str_replace(',', '.', $m[1]);
        };

        $extractByRegex = static function (string $text, string $pattern): ?string {
            if ($text === '') {
                return null;
            }
            if (preg_match($pattern, $text, $m) !== 1) {
                return null;
            }
            $value = trim((string) ($m[1] ?? ''));
            return $value !== '' ? $value : null;
        };

        $xpath = new DOMXPath($dom);
        $pageText = html_entity_decode(trim((string) $dom->textContent), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $title = $scraper->title($dom);
        $headline = $firstText($scraper->xpath($dom, '//h1 | //h2[contains(@class, "uk-h")][1]'));
        if ($headline !== '') {
            $title = $headline;
        }

        $breadcrumbs = $scraper->xpath($dom, '//*[@itemtype="https://schema.org/BreadcrumbList"]//*[@itemprop="name"]');
        $address = null;
        if ($breadcrumbs->length > 0) {
            $last = trim((string) $breadcrumbs->item($breadcrumbs->length - 1)?->textContent);
            $address = $last !== '' ? $last : null;
        }

        $descriptionParts = [];
        $metaDescription = trim((string) ($xpath->query('//meta[@name="description"]')->item(0)?->attributes?->getNamedItem('content')?->nodeValue ?? ''));
        if ($metaDescription !== '') {
            $descriptionParts[] = $metaDescription;
        }
        $carDataTexts = [];
        foreach (['Km/l:', 'CO₂', 'Energimærke:'] as $label) {
            $value = $extractLabeledValue($xpath, $label);
            if ($value !== null) {
                $carDataTexts[] = rtrim($label, ':') . ': ' . $value;
            }
        }
        if ($carDataTexts !== []) {
            $descriptionParts[] = implode(' | ', $carDataTexts);
        }
        $description = $descriptionParts !== [] ? implode("\n\n", $descriptionParts) : null;

        $images = [];
        $imageNodes = $scraper->xpath($dom, '//section[contains(@class, "banner-section")]//a[@href][contains(@class, "uk-icon-button")]');
        foreach ($imageNodes as $imageNode) {
            if (!$imageNode instanceof DOMElement) {
                continue;
            }
            $href = trim((string) $imageNode->getAttribute('href'));
            if ($href === '') {
                continue;
            }
            $images[] = $resolveUrl($href, $url);
        }
        if ($images === []) {
            $fallbackImageNodes = $scraper->xpath($dom, '//section[contains(@class, "banner-section")]//img[@src]');
            foreach ($fallbackImageNodes as $img) {
                if (!$img instanceof DOMElement) {
                    continue;
                }
                $src = trim((string) $img->getAttribute('src'));
                if ($src === '') {
                    continue;
                }
                $images[] = $resolveUrl($src, $url);
            }
        }
        $images = array_values(array_unique($images));

        $kmPerLiter = $extractFloatString($extractLabeledValue($xpath, 'Km/l:'));
        $co2 = $extractLabeledValue($xpath, 'CO₂');

        $price = $extractByRegex($pageText, '/Pris pr\.\s*måned:\s*([0-9\.\, -]+,-)/u');
        $leasingType = $extractByRegex($pageText, '/Leasingtype:\s*(.+?)(?=\s+Km pr\.\s*år:|\s+Brændstof:|\s+Km\/l:|\s+Energiklasse:)/u');
        $kmPerYear = $extractByRegex($pageText, '/Km pr\.\s*år:\s*([0-9\.\, ]+\s*km)/u');
        $fuelType = $extractByRegex($pageText, '/Brændstof:\s*(.+?)(?=\s+Km\/l:|\s+Energiklasse:|\s+Grøn ejerafgift)/u');
        $greenTax = $extractByRegex($pageText, '/Grøn ejerafgift pr\.\s*halvår:\s*([0-9\.\, -]+,-)/u');
        $vehicleNumber = $extractByRegex($pageText, '/Bil nr\.\s*:\s*([0-9]+)/u');
        $color = $extractByRegex($pageText, '/Farve:\s*(.+?)(?=\s+Årgang:|\s+HK:|\s+KM:)/u');
        $year = $extractByRegex($pageText, '/Årgang:\s*(\d{4})/u');
        $horsepower = $extractByRegex($pageText, '/HK:\s*([0-9]+)/u');
        $mileage = $extractByRegex($pageText, '/KM:\s*([0-9\.\,]+)/u');

        $equipment = [];
        $equipmentBlock = $extractByRegex($pageText, '/Udstyr\s+(.+?)(?=\s+Dæktype\s+|\s+Kontakt\s+|\s+Bestil\s+|\s+Tilmeld)/us');
        if ($equipmentBlock !== null) {
            $equipmentBlock = preg_replace('/\s{2,}/u', "\n", $equipmentBlock) ?? $equipmentBlock;
            $lines = preg_split('/\n+/u', $equipmentBlock) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $equipment[] = $line;
            }
        }

        $tireType = $extractByRegex($pageText, '/Dæktype\s+(.+?)(?=\s+Kontakt\s+|\s+Bestil\s+|\s+Tilmeld|\s*$)/us');

        $buildYear = $year !== null && ctype_digit($year) ? (int) $year : null;
        $realtorUrl = 'https://www.bmcleasing.dk/kontakt/';

        return [
            'url' => $url,
            'source' => 'bmcleasing',
            'title' => $title,
            'address' => $address,
            'price' => $price,
            'build_year' => $buildYear,
            'energy_rating' => $extractLabeledValue($xpath, 'Energimærke:'),
            'description' => $description,
            'realtor_url' => $realtorUrl,
            'images' => $images,
            'fuel_efficiency_km_l' => $kmPerLiter,
            'co2_emission' => $co2,
            'monthly_price' => $price,
            'leasing_type' => $leasingType,
            'km_per_year' => $kmPerYear,
            'fuel_type' => $fuelType,
            'green_owner_tax_half_year' => $greenTax,
            'vehicle_number' => $vehicleNumber,
            'color' => $color,
            'year' => $year !== null && ctype_digit($year) ? (int) $year : null,
            'horsepower' => $horsepower !== null && ctype_digit($horsepower) ? (int) $horsepower : null,
            'mileage_km' => $mileage !== null ? (int) str_replace(['.', ','], '', $mileage) : null,
            'equipment' => $equipment,
            'tire_type' => $tireType,
        ];
    }
}
