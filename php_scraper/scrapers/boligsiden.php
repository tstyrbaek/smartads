<?php

declare(strict_types=1);

final class BoligsidenSiteScraper implements SiteScraperInterface
{
    public function supports(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        return $host !== '' && (str_ends_with($host, 'boligsiden.dk') || str_contains($host, '.boligsiden.dk'));
    }

    public function scrape(string $url): array
    {
        $scraper = new Scraper();
        $html = $scraper->fetch($url);
        $dom = $scraper->loadDom($html);

        $firstText = static function (DOMNodeList $nodes): string {
            return trim((string) $nodes->item(0)?->textContent);
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

        $extractByRegex = static function (string $text, string $pattern): ?int {
            if ($text === '') {
                return null;
            }
            if (preg_match($pattern, $text, $m) !== 1) {
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

        $title = $scraper->title($dom);

        $address = $firstText($scraper->xpath($dom, '//*[@id="oversigt"]//h1//span[1]'));
        if ($address === '') {
            $address = $firstText($scraper->xpath($dom, '//*[@id="oversigt"]//h1'));
        }

        $price = $firstText($scraper->xpath($dom, '//*[@id="oversigt"]//h2[contains(normalize-space(.), "kr")]'));

        $overviewNode = $scraper->xpath($dom, '//*[@id="oversigt"]')->item(0);
        $overviewText = $overviewNode instanceof DOMNode ? trim((string) $overviewNode->textContent) : '';

        $livingAreaM2 = $extractByRegex($overviewText, '/Boligareal:\s*(\d[\d\.]*)\s*m\x{00B2}/u');
        $landAreaM2 = $extractByRegex($overviewText, '/Grund:\s*(\d[\d\.]*)\s*m\x{00B2}/u');
        $rooms = $extractByRegex($overviewText, '/\b(\d+)\s*værelser\b/iu');

        $buildYearText = $firstText($scraper->xpath($dom, '//*[@id="oversigt"]//*[self::span or self::div][not(ancestor::h1)][normalize-space(.) != ""][string-length(normalize-space(.))=4 and translate(normalize-space(.), "0123456789", "")=""]'));
        if ($buildYearText === '') {
            $buildYearText = $firstText($scraper->xpath($dom, '//*[@id="oversigt"]//*[self::span or self::div][contains(normalize-space(.), "Byggeår")]'));
        }
        $buildYear = $extractNumber($buildYearText);

        $description = $firstText($scraper->xpath($dom, '//*[@data-name="description"]//p'));
        if ($description !== '') {
            $description = preg_replace("/\n{3,}/", "\n\n", $description) ?? $description;
        }

        $realtorUrl = null;
        $realtorLinkNodes = $scraper->xpath(
            $dom,
            '//a[@href and contains(@href, "/viderestilling/")][contains(normalize-space(.), "Se hos") or contains(normalize-space(.), "Læs mere")]'
        );
        $realtorLinkNode = $realtorLinkNodes->item(0);
        if ($realtorLinkNode instanceof DOMElement) {
            $href = trim((string) $realtorLinkNode->getAttribute('href'));
            if ($href !== '') {
                $realtorUrl = $resolveUrl($href, $url);
            }
        }

        $imageNodes = $scraper->xpath(
            $dom,
            '//img[@src and not(starts-with(@src, "data:")) and (contains(@src, "images.boligsiden.dk") or contains(@src, "/images/case/"))]'
        );
        $images = [];
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

        return [
            'url' => $url,
            'source' => 'boligsiden',
            'title' => $title,
            'address' => $address !== '' ? $address : null,
            'price' => $price !== '' ? $price : null,
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
