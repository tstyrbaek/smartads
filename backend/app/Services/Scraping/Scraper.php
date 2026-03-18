<?php

namespace App\Services\Scraping;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

final class Scraper
{
    private int $timeoutSeconds;

    /**
     * @var array<int, string>
     */
    private array $headers;

    /**
     * @param array<int, string> $headers
     */
    public function __construct(int $timeoutSeconds = 20, array $headers = [])
    {
        $this->timeoutSeconds = $timeoutSeconds;
        $this->headers = $headers;
    }

    public function fetch(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL: ' . $url);
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Failed to init cURL');
        }

        $headers = array_merge([
            'User-Agent: smartads_scraper/1.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ], $this->headers);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new \RuntimeException('cURL error: ' . $error);
        }
        if ($status < 200 || $status >= 400) {
            throw new \RuntimeException('fetch_failed: HTTP ' . $status);
        }
        if (!is_string($body)) {
            throw new \RuntimeException('Empty response body for ' . $url);
        }

        return $body;
    }

    public function loadDom(string $html): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $dom;
    }

    public function title(DOMDocument $dom): ?string
    {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//title');
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->textContent);
        return $value === '' ? null : $value;
    }

    public function query(DOMDocument $dom, string $cssSelector): DOMNodeList
    {
        $xpathSelector = $this->cssToXPath($cssSelector);
        $xpath = new DOMXPath($dom);

        $result = $xpath->query($xpathSelector);
        if ($result === false) {
            throw new \RuntimeException('Invalid selector: ' . $cssSelector);
        }

        return $result;
    }

    public function xpath(DOMDocument $dom, string $xpathSelector): DOMNodeList
    {
        $xpathSelector = trim($xpathSelector);
        if ($xpathSelector === '') {
            throw new \InvalidArgumentException('Selector must not be empty');
        }

        $xpath = new DOMXPath($dom);
        $result = $xpath->query($xpathSelector);
        if ($result === false) {
            throw new \RuntimeException('Invalid selector: ' . $xpathSelector);
        }

        return $result;
    }

    private function cssToXPath(string $selector): string
    {
        $selector = trim($selector);
        if ($selector === '') {
            throw new \InvalidArgumentException('Selector must not be empty');
        }

        $parts = preg_split('/\s+/', $selector) ?: [];
        $xpath = '';

        foreach ($parts as $part) {
            $xpath .= '//' . $this->simpleSelectorToXPath($part);
        }

        return $xpath === '' ? '//*' : $xpath;
    }

    private function simpleSelectorToXPath(string $part): string
    {
        $part = trim($part);
        if ($part === '') {
            return '*';
        }

        $tag = '*';
        $id = null;
        $class = null;

        if (preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*/', $part, $m) === 1) {
            $tag = $m[0];
            $part = substr($part, strlen($m[0]));
        }

        if (preg_match('/#([a-zA-Z0-9_-]+)/', $part, $m) === 1) {
            $id = $m[1];
        }

        if (preg_match('/\.([a-zA-Z0-9_-]+)/', $part, $m) === 1) {
            $class = $m[1];
        }

        $predicates = [];
        if ($id !== null) {
            $predicates[] = '@id=' . $this->xpathLiteral($id);
        }
        if ($class !== null) {
            $predicates[] = 'contains(concat(" ", normalize-space(@class), " "), ' . $this->xpathLiteral(' ' . $class . ' ') . ')';
        }

        if ($predicates === []) {
            return $tag;
        }

        return $tag . '[' . implode(' and ', $predicates) . ']';
    }

    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        $parts = explode("'", $value);
        $out = [];
        foreach ($parts as $i => $p) {
            if ($p !== '') {
                $out[] = "'" . $p . "'";
            }
            if ($i !== count($parts) - 1) {
                $out[] = '"\'"';
            }
        }

        return 'concat(' . implode(',', $out) . ')';
    }

    public function links(DOMDocument $dom, ?string $baseUrl = null): array
    {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//a');
        $links = [];

        if (!$nodes instanceof DOMNodeList) {
            return [];
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $href = trim((string) $node->getAttribute('href'));
            if ($href === '' || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:')) {
                continue;
            }

            $links[] = $this->resolveUrl($href, $baseUrl);
        }

        return array_values(array_unique($links));
    }

    private function resolveUrl(string $href, ?string $baseUrl): string
    {
        if ($baseUrl === null || $baseUrl === '') {
            return $href;
        }

        if (preg_match('~^https?://~i', $href) === 1) {
            return $href;
        }

        $base = parse_url($baseUrl);
        if (!is_array($base) || !isset($base['scheme'], $base['host'])) {
            return $href;
        }

        $scheme = $base['scheme'];
        $host = $base['host'];
        $port = isset($base['port']) ? ':' . $base['port'] : '';

        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }

        $basePath = $base['path'] ?? '/';
        $dir = rtrim(str_replace('\\', '/', dirname($basePath)), '/');

        if (str_starts_with($href, '/')) {
            $path = $href;
        } else {
            $path = ($dir === '' ? '' : $dir) . '/' . $href;
        }

        $path = $this->normalizePath($path);

        return $scheme . '://' . $host . $port . $path;
    }

    private function normalizePath(string $path): string
    {
        $segments = explode('/', $path);
        $out = [];

        foreach ($segments as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                array_pop($out);
                continue;
            }
            $out[] = $seg;
        }

        return '/' . implode('/', $out);
    }
}
