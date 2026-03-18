<?php

declare(strict_types=1);

final class DanboligSiteScraper implements SiteScraperInterface
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

        $title = $scraper->title($dom);

        $address = $firstText($scraper->xpath($dom, '//h1[contains(@class, "o-propertyFacts__address")]'));
        $address = $address !== '' ? preg_replace("/\n{3,}/", "\n\n", $address) ?? $address : '';

        $price = $firstText($scraper->xpath($dom, '//li[contains(@class, "a-label") and contains(@class, "md:u-flex")][.//span[contains(normalize-space(.), "Kontantpris")]]/span[2]'));
        if ($price === '') {
            $price = $firstText($scraper->xpath($dom, '//li[contains(@class, "a-label") and contains(@class, "md:u-none")][1]/span[1]'));
        }

        $livingAreaText = $firstText($scraper->xpath($dom, '//tr[td[1][contains(normalize-space(.), "Boligareal")]]/td[2]'));
        $landAreaText = $firstText($scraper->xpath($dom, '//tr[td[1][contains(normalize-space(.), "Grundareal")]]/td[2]'));
        $roomsText = $firstText($scraper->xpath($dom, '//tr[td[1][normalize-space(.)="Rum"]]/td[2]'));
        $buildYearText = $firstText($scraper->xpath($dom, '//tr[td[1][contains(normalize-space(.), "Byggeår")]]/td[2]'));
        $energyText = $firstText($scraper->xpath($dom, '//tr[td[1][contains(normalize-space(.), "Energimærke")]]/td[2]'));

        $livingAreaM2 = $extractNumber($livingAreaText);
        $rooms = $extractNumber($roomsText);
        $buildYear = $extractNumber($buildYearText);

        $energyRating = null;
        if ($energyText !== '') {
            $energyCandidate = trim($energyText);
            if (preg_match('/\b([A-G])\b/i', $energyCandidate, $m) === 1) {
                $energyRating = strtoupper($m[1]);
            }
        }
        if ($energyRating === null) {
            $energyClass = $firstAttr($scraper->xpath($dom, '//li[contains(@class, "a-label")][.//span[contains(normalize-space(.), "Energimærke")]]//span[contains(@class, "a-energyLabel")]'), 'class');
            if ($energyClass !== '' && preg_match('/a-energyLabel--([a-z0-9]+)/i', $energyClass, $m) === 1) {
                $energyRating = strtoupper($m[1]);
            }
        }

        $landAreaM2 = null;
        if ($landAreaText !== '') {
            if (preg_match('/(\d[\d\.]*)\s*m\x{00B2}/u', $landAreaText, $m) === 1) {
                $landAreaM2 = (int) str_replace('.', '', $m[1]);
            } elseif (preg_match('/(\d+(?:[\.,]\d+)?)\s*ha\b/iu', $landAreaText, $m) === 1) {
                $ha = (float) str_replace(',', '.', $m[1]);
                $landAreaM2 = (int) round($ha * 10000);
            }
        }

        $descriptionTitle = $firstText($scraper->xpath($dom, '//h2[contains(@class, "o-propertyDescription__title")]'));
        $descriptionBody = $firstText($scraper->xpath($dom, '//*[contains(@class, "o-propertyDescription__description")]'));

        $description = trim($descriptionTitle);
        if ($descriptionBody !== '') {
            $descriptionBody = preg_replace("/\n{3,}/", "\n\n", $descriptionBody) ?? $descriptionBody;
            $description = $description !== '' ? ($description . "\n\n" . trim($descriptionBody)) : trim($descriptionBody);
        }

        $realtorUrl = $firstAttr($scraper->xpath($dom, '//*[@id="presentation-broker-link"]'), 'href');
        if ($realtorUrl === '') {
            $realtorUrl = $firstAttr($scraper->xpath($dom, '//section[@id="kontaktmaegler"]//a[@href and contains(@href, "/ejendomsmaegler/")]'), 'href');
        }
        $realtorUrl = $realtorUrl !== '' ? $resolveUrl($realtorUrl, $url) : null;

        $imageNodes = $scraper->xpath($dom, '//*[@id="Galleri"]//img[@src or @srcset]');
        $images = [];
        foreach ($imageNodes as $img) {
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

            $images[] = $resolveUrl($imgUrl, $url);
        }
        $images = array_values(array_unique($images));

        return [
            'url' => $url,
            'source' => 'danbolig',
            'title' => $title,
            'address' => $address !== '' ? $address : null,
            'price' => $price !== '' ? $price : null,
            'living_area_m2' => $livingAreaM2,
            'land_area_m2' => $landAreaM2,
            'rooms' => $rooms,
            'build_year' => $buildYear,
            'energy_rating' => $energyRating,
            'description' => $description !== '' ? $description : null,
            'realtor_url' => $realtorUrl,
            'images' => $images,
        ];
    }
}
