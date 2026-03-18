<?php

namespace App\Services\Scraping\Sites;

use App\Services\Scraping\Scraper;
use App\Services\Scraping\SiteScraperInterface;
use DOMElement;
use DOMNodeList;
use DOMXPath;

class BoesenbaekSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'boesenbaek.dk') || str_contains($host, '.boesenbaek.dk'));
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

        $normalizeText = static function (string $text): string {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace("/\r\n?|\u{00A0}/u", "\n", $text) ?? $text;
            $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
            return trim($text);
        };

        $extractInt = static function (?string $value): ?int {
            if (!is_string($value) || $value === '') {
                return null;
            }
            if (preg_match('/(\d[\d\.]*)/', $value, $m) !== 1) {
                return null;
            }
            $digits = str_replace('.', '', $m[1]);
            return ctype_digit($digits) ? (int) $digits : null;
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

        $extractFieldByLabel = static function (DOMXPath $xpath, string $label) use ($xpathLiteral): ?string {
            $query = sprintf(
                '//p[contains(@class, "bdt_spec_label")][normalize-space(.)=%s]/following-sibling::p[contains(@class, "bdt_spec_value")][1]',
                $xpathLiteral($label)
            );
            $nodes = $xpath->query($query);
            if (!$nodes instanceof DOMNodeList) {
                return null;
            }
            $value = trim((string) $nodes->item(0)?->textContent);
            return $value !== '' ? $value : null;
        };

        $make = $firstText($scraper->xpath($dom, '//div[contains(@class, "carDetailModule")]//h1[1]'));
        $variant = $firstText($scraper->xpath($dom, '//div[contains(@class, "carDetailModule")]//h1[contains(@class, "p1")]'));
        $title = trim($make . ($variant !== '' ? ' ' . $variant : ''));
        if ($title === '') {
            $title = (string) ($scraper->title($dom) ?? '');
        }

        $price = $firstText($scraper->xpath($dom, '//span[contains(@class, "primary-price-details")]'));
        $year = $extractInt($firstText($scraper->xpath($dom, '//div[contains(@class, "usedCarInfo")]//*[contains(normalize-space(.), "Årgang")]/following-sibling::h3[1]')));
        $color = $firstText($scraper->xpath($dom, '//div[contains(@class, "usedCarInfo")]//*[contains(normalize-space(.), "Farve")]/following-sibling::h3[1]'));
        $mileageKm = $extractInt($firstText($scraper->xpath($dom, '//div[contains(@class, "usedCarInfo")]//*[contains(normalize-space(.), "Km")]/following-sibling::h3[1]')));
        $gearType = $firstText($scraper->xpath($dom, '//div[contains(@class, "usedCarInfo")]//*[contains(normalize-space(.), "Geartype")]/following-sibling::h3[1]'));
        $fuelType = $firstText($scraper->xpath($dom, '//div[contains(@class, "usedCarInfo")]//*[contains(normalize-space(.), "Drivmiddel")]/following-sibling::h3[1]'));
        $rangeKm = $firstText($scraper->xpath($dom, '//div[contains(@class, "usedCarInfo")]//*[contains(normalize-space(.), "Rækkevidde")]/following-sibling::h3[1]'));

        $descriptionTitle = $firstText($scraper->xpath($dom, '//h2[normalize-space(.)="Beskrivelse"]'));
        $descriptionBody = $firstText($scraper->xpath($dom, '//div[contains(@class, "et_pb_text_inner")][contains(., "DEMOBIL") or contains(., "Vi tager gerne")]'));
        $description = $normalizeText(trim($descriptionTitle . ($descriptionBody !== '' ? "\n\n" . $descriptionBody : '')));
        if ($description === '') {
            $description = null;
        }

        $equipment = [];
        $equipmentNodes = $scraper->xpath($dom, '//h2[normalize-space(.)="Udstyr"]/ancestor::div[contains(@class, "et_pb_column")][1]//div[contains(@class, "bdt_table")]//div[contains(@class, "col-6")]');
        foreach ($equipmentNodes as $node) {
            $value = trim((string) $node->textContent);
            if ($value !== '') {
                $equipment[] = $value;
            }
        }
        $equipment = array_values(array_unique($equipment));

        $images = [];
        $imageNodes = $scraper->xpath($dom, '//section[contains(@class, "bt-slideshow")]//img[@src]');
        foreach ($imageNodes as $img) {
            if (!$img instanceof DOMElement) {
                continue;
            }
            $src = trim((string) $img->getAttribute('src'));
            if ($src === '') {
                continue;
            }
            $images[] = $resolveUrl($src, $url);
        }
        $images = array_values(array_unique($images));

        $annualGreenTax = $extractFieldByLabel($xpath, 'Årlig grøn ejerafgift');
        $kmPerLiter = $extractFieldByLabel($xpath, 'Km/L');
        $co2Emission = $extractFieldByLabel($xpath, 'CO2');
        $horsepowerText = $extractFieldByLabel($xpath, 'Maksimal effekt');
        $horsepower = $horsepowerText !== null && preg_match('/(\d+)/', $horsepowerText, $m) === 1 ? (int) $m[1] : null;
        $bodyType = $extractFieldByLabel($xpath, 'Karosseri');
        $doorCount = $extractInt($extractFieldByLabel($xpath, 'Antal døre'));
        $seatCount = $extractInt($extractFieldByLabel($xpath, 'Antal sæder'));
        $dealerReference = $extractFieldByLabel($xpath, 'Forhandler referencenummer');
        $firstRegistrationDate = $extractFieldByLabel($xpath, '1. indregistreringsdato');
        $batteryCapacity = $extractFieldByLabel($xpath, 'Batteristørrelse (kWh)');
        $dcCharge = $extractFieldByLabel($xpath, 'Ladeeffekt DC');
        $acCharge = $extractFieldByLabel($xpath, 'Ladeeffekt AC');

        $realtorUrl = null;
        $emailLink = $scraper->xpath($dom, '//a[@href and contains(@href, "/forespoergsel/")]')->item(0);
        if ($emailLink instanceof DOMElement) {
            $href = trim((string) $emailLink->getAttribute('href'));
            if ($href !== '') {
                $realtorUrl = $resolveUrl($href, $url);
            }
        }
        if ($realtorUrl === null) {
            $realtorUrl = 'https://www.boesenbaek.dk/kontakt/';
        }

        return [
            'url' => $url,
            'source' => 'boesenbaek',
            'title' => $title,
            'address' => null,
            'price' => $price !== '' ? $price : null,
            'build_year' => $year,
            'energy_rating' => null,
            'description' => $description,
            'realtor_url' => $realtorUrl,
            'images' => $images,
            'year' => $year,
            'color' => $color !== '' ? $color : null,
            'mileage_km' => $mileageKm,
            'gear_type' => $gearType !== '' ? $gearType : null,
            'fuel_type' => $fuelType !== '' ? $fuelType : null,
            'range_km' => $rangeKm !== '' ? $rangeKm : null,
            'annual_green_owner_tax' => $annualGreenTax,
            'fuel_efficiency_km_l' => $kmPerLiter,
            'co2_emission' => $co2Emission,
            'horsepower' => $horsepower,
            'equipment' => $equipment,
            'body_type' => $bodyType,
            'door_count' => $doorCount,
            'seat_count' => $seatCount,
            'dealer_reference_number' => $dealerReference,
            'first_registration_date' => $firstRegistrationDate,
            'battery_capacity' => $batteryCapacity,
            'dc_charge_power' => $dcCharge,
            'ac_charge_power' => $acCharge,
        ];
    }
}
