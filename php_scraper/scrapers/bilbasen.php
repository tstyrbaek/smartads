<?php

declare(strict_types=1);

final class BilbasenSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'bilbasen.dk') || str_contains($host, '.bilbasen.dk'));
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

        $extractInt = static function (?string $value): ?int {
            if (!is_string($value) || trim($value) === '') {
                return null;
            }
            if (preg_match('/(\d[\d\.]*)/', $value, $m) !== 1) {
                return null;
            }
            $digits = str_replace('.', '', $m[1]);
            return ctype_digit($digits) ? (int) $digits : null;
        };

        $extractFloatString = static function (?string $value): ?string {
            if (!is_string($value) || trim($value) === '') {
                return null;
            }
            if (preg_match('/(\d+(?:[\.,]\d+)?)/', $value, $m) !== 1) {
                return null;
            }
            return str_replace(',', '.', $m[1]);
        };

        $extractTableData = static function (DOMXPath $xpath, string $tableSelector): array {
            $rows = $xpath->query($tableSelector);
            $result = [];
            if (!$rows instanceof DOMNodeList) {
                return $result;
            }

            foreach ($rows as $row) {
                if (!$row instanceof DOMElement) {
                    continue;
                }
                $label = trim((string) ($xpath->query('./th[1]', $row)?->item(0)?->textContent ?? ''));
                $value = trim((string) ($xpath->query('./td[1]', $row)?->item(0)?->textContent ?? ''));
                if ($label === '' || $value === '') {
                    continue;
                }
                $result[$label] = $value;
            }

            return $result;
        };

        $title = $firstText($scraper->xpath($dom, '//h1[@data-e2e="car-make-model-variant"]'));
        $variant = $firstText($scraper->xpath($dom, '//h1[@data-e2e="car-make-model-variant"]//span[contains(@class, "variant")]'));
        if ($variant !== '' && str_ends_with($title, $variant)) {
            $title = trim(substr($title, 0, -strlen($variant)));
        }
        $fullTitle = trim($title . ($variant !== '' ? ' ' . $variant : ''));
        if ($fullTitle === '') {
            $fullTitle = (string) ($scraper->title($dom) ?? '');
        }

        $price = $firstText($scraper->xpath($dom, '//*[@data-e2e="car-retail-price"]'));
        $monthlyPrice = $firstText($scraper->xpath($dom, '//*[contains(@class, "monthly-price-value")][1]'));
        $financeCompany = $firstText($scraper->xpath($dom, '//*[@data-e2e="finance-company"]'));

        $detailFacts = $extractTableData($xpath, '//table[@data-e2e="car-facts-table-preview"]//tr | //table[@data-e2e="car-facts-table-collapsed"]//tr');
        $modelInfo = $extractTableData($xpath, '//table[@data-e2e="model-information-table-preview"]//tr | //table[@data-e2e="model-information-table-collapsed"]//tr');

        $description = $normalizeText($firstText($scraper->xpath($dom, '//div[contains(@class, "bas-MuiAdDescriptionComponent-descriptionText")][1]')));

        $equipment = [];
        $equipmentNodes = $scraper->xpath($dom, '//*[@data-e2e="car-equipment-item"]');
        foreach ($equipmentNodes as $equipmentNode) {
            $value = trim((string) $equipmentNode->textContent);
            if ($value !== '') {
                $equipment[] = $value;
            }
        }
        $equipment = array_values(array_unique($equipment));

        $images = [];
        if (preg_match_all('~https://billeder\.bilbasen\.dk/bilinfo/[^"\'\\s>]+~i', $html, $imageMatches) === 1 || !empty($imageMatches[0])) {
            foreach ($imageMatches[0] as $matchedImage) {
                $images[] = html_entity_decode(trim($matchedImage), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        $images = array_values(array_unique($images));

        $year = $extractInt($detailFacts['Modelår'] ?? null);
        $firstRegistration = $detailFacts['1. registrering'] ?? null;
        $mileage = $extractInt($detailFacts['Kilometertal'] ?? null);
        $fuelType = $detailFacts['Drivmiddel'] ?? null;
        $fuelEfficiency = $extractFloatString($detailFacts['Brændstofforbrug'] ?? null);
        $co2Emission = $detailFacts['CO2 udledning'] ?? null;
        $greenTax = $detailFacts['Periodisk afgift'] ?? null;
        $performance = $detailFacts['Ydelse'] ?? null;
        $horsepower = $extractInt($performance);
        $torque = null;
        if (is_string($performance) && preg_match('/\/(\d+)\s*nm/i', $performance, $m) === 1) {
            $torque = $m[1] . ' nm';
        }
        $acceleration = $detailFacts['Acceleration'] ?? null;
        $topSpeed = $detailFacts['Tophastighed'] ?? null;
        $gearType = $detailFacts['Geartype'] ?? null;
        $gearCount = $extractInt($detailFacts['Antal gear'] ?? null);
        $trailerWeight = $detailFacts['Trækvægt'] ?? ($modelInfo['Max. trækvægt m/bremse'] ?? null);
        $color = $detailFacts['Farve'] ?? null;

        $bodyType = $modelInfo['Type'] ?? null;
        $category = $modelInfo['Kategori'] ?? null;
        $newPrice = $modelInfo['Nypris'] ?? null;
        $trunkCapacity = $modelInfo['Bagagerumsstørrelse'] ?? null;
        $weight = $modelInfo['Vægt'] ?? null;
        $width = $modelInfo['Bredde'] ?? null;
        $length = $modelInfo['Længde'] ?? null;
        $height = $modelInfo['Højde'] ?? null;
        $payload = $modelInfo['Lasteevne'] ?? null;
        $drivetrain = $modelInfo['Trækhjul'] ?? null;
        $cylinders = $extractInt($modelInfo['Cylindre'] ?? null);
        $airbags = $extractInt($modelInfo['Airbags'] ?? null);
        $tankCapacity = $modelInfo['Tankkapacitet'] ?? null;
        $doors = $extractInt($modelInfo['Døre'] ?? null);

        return [
            'url' => $url,
            'source' => 'bilbasen',
            'title' => $fullTitle !== '' ? $fullTitle : null,
            'address' => null,
            'price' => $price !== '' ? $price : null,
            'build_year' => $year,
            'description' => $description,
            'realtor_url' => $url,
            'images' => $images,
            'year' => $year,
            'first_registration_date' => $firstRegistration,
            'mileage_km' => $mileage,
            'fuel_type' => $fuelType,
            'fuel_efficiency_km_l' => $fuelEfficiency,
            'co2_emission' => $co2Emission,
            'annual_green_owner_tax' => $greenTax,
            'horsepower' => $horsepower,
            'torque' => $torque,
            'zero_to_hundred' => $acceleration,
            'top_speed' => $topSpeed,
            'gear_type' => $gearType,
            'gear_count' => $gearCount,
            'trailer_weight' => $trailerWeight,
            'color' => $color,
            'body_type' => $bodyType,
            'category' => $category,
            'new_price' => $newPrice,
            'trunk_capacity' => $trunkCapacity,
            'weight' => $weight,
            'width' => $width,
            'length' => $length,
            'height' => $height,
            'payload' => $payload,
            'transmission' => $drivetrain,
            'cylinders' => $cylinders,
            'airbags' => $airbags,
            'tank_capacity' => $tankCapacity,
            'door_count' => $doors,
            'equipment' => $equipment,
            'specifications' => [
                'details' => $detailFacts,
                'model_information' => $modelInfo,
            ],
            'monthly_finance_price' => $monthlyPrice !== '' ? $monthlyPrice : null,
            'finance_company' => $financeCompany !== '' ? $financeCompany : null,
        ];
    }
}
