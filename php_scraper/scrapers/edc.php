<?php

declare(strict_types=1);

final class EdcSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'edc.dk') || str_contains($host, '.edc.dk'));
    }

    public function scrape(string $url): array
    {
        $scraper = new Scraper();
        $html = $scraper->fetch($url);
        $dom = $scraper->loadDom($html);

        $firstText = static function (DOMNodeList $nodes): string {
            return trim((string) $nodes->item(0)?->textContent);
        };

        $firstAttr = static function (DOMNodeList $nodes, string $attr): string {
            $node = $nodes->item(0);
            if (!$node instanceof DOMElement) {
                return '';
            }
            return trim((string) $node->getAttribute($attr));
        };

        $extractNumber = static function (string $value): ?int {
            if ($value === '') {
                return null;
            }
            if (preg_match('/(\d[\d\.]*)/', $value, $m) !== 1) {
                return null;
            }
            $digits = str_replace('.', '', $m[1]);
            return ctype_digit($digits) ? (int) $digits : null;
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

        $bestFromSrcset = static function (string $srcset): string {
            $srcset = trim($srcset);
            if ($srcset === '') {
                return '';
            }

            $bestUrl = '';
            $bestWidth = -1;

            foreach (explode(',', $srcset) as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $chunks = preg_split('/\s+/', $part) ?: [];
                $u = trim((string) ($chunks[0] ?? ''));
                $descriptor = trim((string) ($chunks[1] ?? ''));

                $width = -1;
                if ($descriptor !== '' && preg_match('/^(\d+)w$/', $descriptor, $m) === 1) {
                    $width = (int) $m[1];
                }

                if ($u !== '' && $width >= $bestWidth) {
                    $bestWidth = $width;
                    $bestUrl = $u;
                }
            }

            return $bestUrl;
        };

        $normalizeEdcImageUrl = static function (string $imgUrl): string {
            $imgUrl = trim($imgUrl);
            if ($imgUrl === '') {
                return '';
            }

            if (!str_contains($imgUrl, 'billeder.edc.dk/edcmedia/')) {
                return $imgUrl;
            }

            if (preg_match('~_Size\d+x\d+(?:crop)?\.(?:jpe?g|png|webp)(?:\?.*)?$~i', $imgUrl) === 1) {
                return $imgUrl;
            }

            if (preg_match('~^(https?://[^?]+)\.(jpe?g|png|webp)(\?.*)?$~i', $imgUrl, $m) !== 1) {
                return $imgUrl;
            }

            $base = $m[1];
            $ext = $m[2];
            $qs = $m[3] ?? '';

            return $base . '_Size1920x1080.' . $ext . $qs;
        };

        $title = $scraper->title($dom);

        $description = $firstText($scraper->xpath($dom, '//*[@itemprop="description"]'));
        if ($description !== '') {
            $description = str_replace("\u{00AD}", '', $description);
            $description = preg_replace("/\n{3,}/", "\n\n", $description) ?? $description;
        }
        if ($description === '') {
            $metaDescription = $firstAttr($scraper->xpath($dom, '//meta[@name="description"]'), 'content');
            $ogDescription = $firstAttr($scraper->xpath($dom, '//meta[@property="og:description"]'), 'content');
            $description = $metaDescription !== '' ? $metaDescription : $ogDescription;
        }

        $street = $firstText($scraper->xpath($dom, '//*[@itemprop="streetAddress"]'));
        $postalCode = $firstText($scraper->xpath($dom, '//*[@itemprop="postalCode"]'));
        $locality = $firstText($scraper->xpath($dom, '//*[@itemprop="addressLocality"]'));
        $addressParts = array_values(array_filter([$street, trim($postalCode . ' ' . $locality)]));
        $address = $addressParts !== [] ? implode("\n", $addressParts) : null;

        $priceText = $firstText($scraper->xpath($dom, '//*[@itemprop="price"]'));
        $price = $priceText !== '' ? $priceText : null;

        $livingAreaM2 = null;
        $landAreaM2 = null;
        $rooms = null;
        $buildYear = null;
        $realtorUrl = null;
        $images = [];

        $jsonLdNodes = $scraper->xpath($dom, '//script[@type="application/ld+json"]');
        foreach ($jsonLdNodes as $node) {
            $json = trim((string) $node->textContent);
            if ($json === '') {
                continue;
            }

            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                continue;
            }

            $candidates = [];
            if (isset($decoded['@type'])) {
                $candidates[] = $decoded;
            } elseif (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
                foreach ($decoded['@graph'] as $g) {
                    if (is_array($g)) {
                        $candidates[] = $g;
                    }
                }
            }

            foreach ($candidates as $cand) {
                if (($cand['@type'] ?? null) !== 'Offer') {
                    continue;
                }

                if (is_int($cand['price'] ?? null)) {
                    $price = number_format((int) $cand['price'], 0, ',', '.') . ' kr.';
                }

                $sellerUrl = $cand['seller']['url'] ?? null;
                if (is_string($sellerUrl) && $sellerUrl !== '') {
                    $realtorUrl = $resolveUrl($sellerUrl, $url);
                }

                $item = $cand['itemOffered'] ?? null;
                if (is_array($item)) {
                    $props = $item['additionalProperty'] ?? null;
                    if (is_array($props)) {
                        foreach ($props as $prop) {
                            if (!is_array($prop)) {
                                continue;
                            }
                            $name = $prop['name'] ?? null;
                            $value = $prop['value'] ?? null;

                            if ($name === 'LivingArea') {
                                $livingAreaM2 = $extractNumber(is_array($value) ? (string) ($value['description'] ?? '') : (string) $value);
                            }
                            if ($name === 'AreaLand') {
                                $landAreaM2 = $extractNumber(is_array($value) ? (string) ($value['description'] ?? '') : (string) $value);
                            }
                            if ($name === 'YearBuilt') {
                                if (is_int($value)) {
                                    $buildYear = $value;
                                } elseif (is_string($value)) {
                                    $buildYear = $extractNumber($value);
                                }
                            }
                        }
                    }

                    $roomsCandidate = $item['numberOfRooms']['value'] ?? ($item['accommodationFloorPlan']['numberOfRooms'] ?? null);
                    if (is_numeric($roomsCandidate)) {
                        $rooms = (int) $roomsCandidate;
                    }

                    $layoutImages = $item['accommodationFloorPlan']['layoutImage'] ?? null;
                    if (is_array($layoutImages)) {
                        foreach ($layoutImages as $img) {
                            if (!is_array($img)) {
                                continue;
                            }
                            $imgUrl = $img['url'] ?? null;
                            if (is_string($imgUrl) && $imgUrl !== '') {
                                $images[] = $normalizeEdcImageUrl($resolveUrl($imgUrl, $url));
                            }
                        }
                    }
                }

                $offerImage = $cand['image']['url'] ?? null;
                if (is_string($offerImage) && $offerImage !== '') {
                    $images[] = $normalizeEdcImageUrl($resolveUrl($offerImage, $url));
                }
            }
        }

        $galleryNodes = $scraper->xpath(
            $dom,
            '//*[@id="case-gallery"]//img[@src or @srcset]'
        );
        foreach ($galleryNodes as $img) {
            if (!$img instanceof DOMElement) {
                continue;
            }
            $srcset = trim((string) $img->getAttribute('srcset'));
            $src = trim((string) $img->getAttribute('src'));

            $imgUrl = $bestFromSrcset($srcset);
            if ($imgUrl === '') {
                $imgUrl = $src;
            }
            if ($imgUrl === '') {
                continue;
            }

            $resolved = $resolveUrl($imgUrl, $url);
            if ($resolved === '' || !str_contains($resolved, 'billeder.edc.dk/edcmedia/')) {
                continue;
            }

            $images[] = $normalizeEdcImageUrl($resolved);
        }

        if ($livingAreaM2 === null || $landAreaM2 === null || $rooms === null || $buildYear === null) {
            $facts = $firstText($scraper->xpath($dom, '//*[@id="case-about"]'));
            if ($facts !== '') {
                $livingAreaM2 = $livingAreaM2 ?? (preg_match('/Boligareal\s*(\d[\d\.]*)\s*m\x{00B2}/u', $facts, $m) === 1 ? (int) str_replace('.', '', $m[1]) : null);
                $landAreaM2 = $landAreaM2 ?? (preg_match('/Grundareal\s*(\d[\d\.]*)\s*m\x{00B2}/u', $facts, $m) === 1 ? (int) str_replace('.', '', $m[1]) : null);
                $buildYear = $buildYear ?? (preg_match('/Byggeår\s*(\d{4})/u', $facts, $m) === 1 ? (int) $m[1] : null);
                $rooms = $rooms ?? (preg_match('/Rum\/værelser\s*(\d+)\//u', $facts, $m) === 1 ? (int) $m[1] : null);
            }
        }

        if ($images === []) {
            $imgNodes = $scraper->xpath($dom, '//img[@src and contains(@src, "billeder.edc.dk")]');
            foreach ($imgNodes as $img) {
                if (!$img instanceof DOMElement) {
                    continue;
                }
                $src = trim((string) $img->getAttribute('src'));
                if ($src === '') {
                    continue;
                }
                $images[] = $normalizeEdcImageUrl($resolveUrl($src, $url));
            }
        }

        $images = array_values(array_unique($images));

        return [
            'url' => $url,
            'source' => 'edc',
            'title' => $title,
            'address' => $address,
            'price' => $price,
            'living_area_m2' => $livingAreaM2,
            'land_area_m2' => $landAreaM2,
            'rooms' => $rooms,
            'build_year' => $buildYear,
            'description' => $description !== '' ? $description : null,
            'realtor_url' => $realtorUrl,
            'images' => $images,
        ];
    }
}
