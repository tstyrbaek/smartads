<?php

declare(strict_types=1);

final class SebilerSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'sebiler.dk') || str_contains($host, '.sebiler.dk'));
    }

    public function scrape(string $url): array
    {
        $scraper = new Scraper();
        $html = $scraper->fetch($url);
        $apiScraper = new Scraper(20, ['Accept: application/json,text/plain,*/*']);

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

        $extractInt = static function ($value): ?int {
            if (!is_scalar($value)) {
                return null;
            }
            $stringValue = trim((string) $value);
            if ($stringValue === '') {
                return null;
            }
            if (preg_match('/(\d[\d\.]*)/', $stringValue, $m) !== 1) {
                return null;
            }
            $digits = str_replace('.', '', $m[1]);
            return ctype_digit($digits) ? (int) $digits : null;
        };

        $extractFloatString = static function ($value): ?string {
            if (!is_scalar($value)) {
                return null;
            }
            $stringValue = trim((string) $value);
            if ($stringValue === '') {
                return null;
            }
            if (preg_match('/(\d+(?:[\.,]\d+)?)/', $stringValue, $m) !== 1) {
                return null;
            }
            return str_replace(',', '.', $m[1]);
        };

        $formatThousands = static function ($value): ?string {
            if (!is_scalar($value) || !is_numeric((string) $value)) {
                return null;
            }
            return number_format((float) $value, 0, ',', '.');
        };

        if (preg_match('/bid-(\d+)/i', $url, $idMatch) !== 1) {
            throw new RuntimeException('Could not determine Sebiler vehicle id from URL');
        }
        $vehicleId = $idMatch[1];

        if (preg_match('~<script[^>]+id="usedcarsmodule"[^>]+src="([^"]+)"~i', $html, $scriptMatch) !== 1) {
            throw new RuntimeException('Could not locate Brugtbilsmodulet script on Sebiler page');
        }
        $moduleScriptUrl = $resolveUrl(html_entity_decode($scriptMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $url);
        $moduleScript = $scraper->fetch($moduleScriptUrl);

        if (preg_match('/listId:\s*(\d+)/', $moduleScript, $listIdMatch) !== 1) {
            throw new RuntimeException('Could not determine Sebiler list id from Brugtbilsmodulet script');
        }
        $listId = $listIdMatch[1];

        $apiUrl = 'https://www.brugtbilsmodulet.dk/umbraco/api/data/getvehicle?currentPageId=' . rawurlencode($listId) . '&id=' . rawurlencode($vehicleId);
        $apiResponse = $apiScraper->fetch($apiUrl);
        $apiResponse = preg_replace('/^\xEF\xBB\xBF/', '', $apiResponse) ?? $apiResponse;
        $decoded = json_decode($apiResponse, true);
        if (!is_array($decoded)) {
            $decoded = json_decode($apiResponse, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
        }
        if (!is_array($decoded) && str_contains($apiResponse, '"Data"')) {
            $start = strpos($apiResponse, '{');
            $end = strrpos($apiResponse, '}');
            if ($start !== false && $end !== false && $end >= $start) {
                $decoded = json_decode(substr($apiResponse, $start, $end - $start + 1), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
            }
        }
        $data = is_array($decoded) ? ($decoded['Data'] ?? null) : null;
        if ($data instanceof stdClass) {
            $data = (array) $data;
        }
        if (!is_array($data)) {
            throw new RuntimeException(
                'Invalid Sebiler vehicle response from Brugtbilsmodulet API: ' .
                (is_array($decoded) ? 'missing Data' : json_last_error_msg())
            );
        }

        $car = $data;

        $titleParts = [];
        foreach (['Make', 'Model', 'Variant'] as $key) {
            $value = trim((string) ($car[$key] ?? ''));
            if ($value !== '') {
                $titleParts[] = $value;
            }
        }
        $title = $titleParts !== [] ? implode(' ', $titleParts) : null;

        $price = null;
        $displayPrice = trim((string) ($car['DisplayPrice'] ?? ''));
        if ($displayPrice !== '') {
            $price = 'DKK ' . $displayPrice;
        } elseif (($formattedPrice = $formatThousands($car['Price'] ?? null)) !== null) {
            $price = 'DKK ' . $formattedPrice . ',-';
        }

        $dealerAddressParts = array_filter([
            trim((string) ($car['DealerAddressStreetLine1'] ?? '')),
            trim((string) ($car['DealerAddressStreetLine2'] ?? '')),
            trim((string) ($car['DealerAddressZipCode'] ?? '')),
            trim((string) ($car['DealerAddressCity'] ?? '')),
        ], static fn ($value): bool => is_string($value) && $value !== '');
        $dealerAddress = $dealerAddressParts !== [] ? implode(', ', $dealerAddressParts) : null;

        $monthlyFinancePrice = null;
        if (($formattedFinance = $formatThousands($car['FinancePriceMonthly'] ?? null)) !== null) {
            $monthlyFinancePrice = 'Kr. fra ' . $formattedFinance . ',- pr. måned';
        }

        $specifications = [
            'Generelt' => array_filter([
                '1.reg.' => trim((string) ($car['RegistrationDate'] ?? '')),
                'Type' => trim((string) ($car['Type'] ?? '')),
                'Farve' => trim((string) ($car['Color'] ?? '')),
                'Modelår' => trim((string) ($car['Year'] ?? '')),
                'Antal døre' => trim((string) ($car['NumberOfDoors'] ?? '')),
                'Produktionsår' => trim((string) ($car['ProductionYear'] ?? '')),
            ], static fn ($value): bool => is_string($value) && $value !== ''),
            'Motor' => array_filter([
                'Km.' => ($formattedMileage = $formatThousands($car['Mileage'] ?? null)) !== null ? $formattedMileage : '',
                'Brændstof' => trim((string) ($car['Propellant'] ?? '')),
                'Motor' => trim((string) ($car['Motor'] ?? '')) !== '' ? trim((string) $car['Motor']) . ' l' : '',
                'Cylindere' => trim((string) ($car['Cylinders'] ?? '')),
                'Ventiler' => trim((string) ($car['ValvesPerCylinder'] ?? '')),
                'HK' => trim((string) ($car['Effect'] ?? '')),
                'Volume' => trim((string) ($car['MotorVolume'] ?? '')),
                'Transmission' => trim((string) ($car['DriveWheels'] ?? '')),
                'Gear type' => trim((string) ($car['GearType'] ?? '')),
            ], static fn ($value): bool => is_string($value) && $value !== ''),
            'Ydelse' => array_filter([
                'Km. pr. liter' => trim((string) ($car['KmPerLiter'] ?? '')),
                'Topfart' => trim((string) ($car['TopSpeed'] ?? '')) !== '' ? trim((string) $car['TopSpeed']) . ' km/t.' : '',
                'Moment' => trim((string) ($car['EffectInNm'] ?? '')) !== '' ? trim((string) $car['EffectInNm']) . ' nm' : '',
                '0-100 km/t.' => trim((string) ($car['Acceleration0To100'] ?? '')) !== '' ? trim((string) $car['Acceleration0To100']) . ' sek.' : '',
            ], static fn ($value): bool => is_string($value) && $value !== ''),
            'Egenskaber' => array_filter([
                'Længde' => trim((string) ($car['Length'] ?? '')) !== '' ? trim((string) $car['Length']) . ' cm.' : '',
                'Bredde' => trim((string) ($car['Width'] ?? '')) !== '' ? trim((string) $car['Width']) . ' cm.' : '',
                'Højde' => trim((string) ($car['Height'] ?? '')) !== '' ? trim((string) $car['Height']) . ' cm.' : '',
                'Totalvægt' => trim((string) ($car['TotalWeight'] ?? '')) !== '' ? trim((string) $car['TotalWeight']) . ' kg.' : '',
                'Tank' => trim((string) ($car['GasTankMax'] ?? '')) !== '' ? trim((string) $car['GasTankMax']) . ' l.' : '',
                'Træk' => trim((string) ($car['TrailerWeight'] ?? '')) !== '' ? trim((string) $car['TrailerWeight']) . ' kg.' : '',
                'Vægt' => trim((string) ($car['Weight'] ?? '')) !== '' ? trim((string) $car['Weight']) . ' kg.' : '',
            ], static fn ($value): bool => is_string($value) && $value !== ''),
            'Økonomi' => array_filter([
                'Grøn ejerafgift' => trim((string) ($car['GreenTax'] ?? '')) !== ''
                    ? trim((string) $car['GreenTax']) . ' Kr. / årligt'
                    : '',
            ], static fn ($value): bool => is_string($value) && $value !== ''),
        ];
        $specifications = array_filter($specifications, static fn ($section): bool => is_array($section) && $section !== []);

        $images = [];
        if (isset($car['Pictures']) && is_array($car['Pictures'])) {
            foreach ($car['Pictures'] as $image) {
                if (!is_string($image) || trim($image) === '') {
                    continue;
                }
                $images[] = $image;
            }
        }
        if (isset($car['Video']) && is_string($car['Video']) && trim($car['Video']) !== '') {
            $images[] = trim($car['Video']);
        }
        $images = array_values(array_unique($images));

        $equipment = [];
        if (isset($car['EquipmentList']) && is_array($car['EquipmentList'])) {
            foreach ($car['EquipmentList'] as $item) {
                if (!is_string($item) || trim($item) === '') {
                    continue;
                }
                $equipment[] = trim($item);
            }
        }
        $equipment = array_values(array_unique($equipment));

        return [
            'url' => $url,
            'source' => 'sebiler',
            'title' => $title,
            'address' => $dealerAddress,
            'price' => $price,
            'build_year' => $extractInt($car['Year'] ?? null),
            'description' => trim((string) ($car['Comment'] ?? '')) !== '' ? trim((string) $car['Comment']) : null,
            'realtor_url' => $url,
            'images' => $images,
            'year' => $extractInt($car['Year'] ?? null),
            'first_registration_date' => trim((string) ($car['RegistrationDate'] ?? '')) !== '' ? trim((string) $car['RegistrationDate']) : null,
            'body_type' => trim((string) ($car['BodyType'] ?? '')) !== '' ? trim((string) $car['BodyType']) : (trim((string) ($car['Type'] ?? '')) !== '' ? trim((string) $car['Type']) : null),
            'mileage_km' => $extractInt($car['Mileage'] ?? null),
            'color' => trim((string) ($car['Color'] ?? '')) !== '' ? trim((string) $car['Color']) : null,
            'fuel_type' => trim((string) ($car['Propellant'] ?? '')) !== '' ? trim((string) $car['Propellant']) : null,
            'engine_size' => trim((string) ($car['Motor'] ?? '')) !== '' ? trim((string) $car['Motor']) . ' l' : null,
            'fuel_efficiency_km_l' => $extractFloatString($car['KmPerLiter'] ?? null),
            'door_count' => $extractInt($car['NumberOfDoors'] ?? null),
            'horsepower' => $extractInt($car['Effect'] ?? null),
            'gear_type' => trim((string) ($car['GearType'] ?? '')) !== '' ? trim((string) $car['GearType']) : null,
            'transmission' => trim((string) ($car['DriveWheels'] ?? '')) !== '' ? trim((string) ($car['DriveWheels'] ?? '')) : null,
            'cylinders' => $extractInt($car['Cylinders'] ?? null),
            'top_speed' => trim((string) ($car['TopSpeed'] ?? '')) !== '' ? trim((string) $car['TopSpeed']) . ' km/t.' : null,
            'torque' => trim((string) ($car['EffectInNm'] ?? '')) !== '' ? trim((string) $car['EffectInNm']) . ' nm' : null,
            'zero_to_hundred' => trim((string) ($car['Acceleration0To100'] ?? '')) !== '' ? trim((string) $car['Acceleration0To100']) . ' sek.' : null,
            'length' => trim((string) ($car['Length'] ?? '')) !== '' ? trim((string) $car['Length']) . ' cm.' : null,
            'width' => trim((string) ($car['Width'] ?? '')) !== '' ? trim((string) $car['Width']) . ' cm.' : null,
            'height' => trim((string) ($car['Height'] ?? '')) !== '' ? trim((string) $car['Height']) . ' cm.' : null,
            'weight' => trim((string) ($car['Weight'] ?? '')) !== '' ? trim((string) $car['Weight']) . ' kg.' : null,
            'gross_weight' => trim((string) ($car['TotalWeight'] ?? '')) !== '' ? trim((string) $car['TotalWeight']) . ' kg.' : null,
            'tank_capacity' => trim((string) ($car['GasTankMax'] ?? '')) !== '' ? trim((string) $car['GasTankMax']) . ' l.' : null,
            'annual_green_owner_tax' => trim((string) ($car['GreenTax'] ?? '')) !== '' ? trim((string) $car['GreenTax']) . ' Kr. / årligt' : null,
            'equipment' => $equipment,
            'specifications' => $specifications,
            'dealer_name' => trim((string) ($car['DealerName'] ?? '')) !== '' ? trim((string) $car['DealerName']) : null,
            'dealer_phone' => trim((string) ($car['DealerPhoneNumber'] ?? '')) !== '' ? trim((string) $car['DealerPhoneNumber']) : null,
            'dealer_email' => trim((string) ($car['DealerEmail'] ?? '')) !== '' ? trim((string) $car['DealerEmail']) : null,
            'monthly_finance_price' => $monthlyFinancePrice,
            'vehicle_id' => trim((string) ($car['VehicleId'] ?? '')) !== '' ? trim((string) $car['VehicleId']) : null,
            'registration_number' => trim((string) ($car['RegistrationNumber'] ?? '')) !== '' ? trim((string) $car['RegistrationNumber']) : null,
        ];
    }
}
