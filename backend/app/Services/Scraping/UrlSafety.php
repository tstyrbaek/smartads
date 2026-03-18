<?php

namespace App\Services\Scraping;

class UrlSafety
{
    public function isSafe(string $url): bool
    {
        try {
            $this->assertSafe($url);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function assertSafe(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            throw new \InvalidArgumentException('invalid_url');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('invalid_url');
        }

        $host = (string) ($parts['host'] ?? '');
        if ($host === '') {
            throw new \InvalidArgumentException('invalid_url');
        }

        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            $records = dns_get_record($host, DNS_A + DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $r) {
                    if (isset($r['ip']) && is_string($r['ip'])) {
                        $ips[] = $r['ip'];
                    }
                    if (isset($r['ipv6']) && is_string($r['ipv6'])) {
                        $ips[] = $r['ipv6'];
                    }
                }
            }
        }

        if ($ips === []) {
            throw new \InvalidArgumentException('invalid_url');
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                throw new \InvalidArgumentException('unsafe_url');
            }
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long === false) return true;

            $ranges = [
                ['0.0.0.0', '0.255.255.255'],
                ['10.0.0.0', '10.255.255.255'],
                ['127.0.0.0', '127.255.255.255'],
                ['169.254.0.0', '169.254.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.168.0.0', '192.168.255.255'],
                ['224.0.0.0', '239.255.255.255'],
            ];

            foreach ($ranges as [$start, $end]) {
                $s = ip2long($start);
                $e = ip2long($end);
                if ($s === false || $e === false) continue;
                if ($long >= $s && $long <= $e) {
                    return true;
                }
            }

            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipLower = strtolower($ip);
            if ($ipLower === '::1') return true;
            if (str_starts_with($ipLower, 'fc') || str_starts_with($ipLower, 'fd')) return true;
            if (str_starts_with($ipLower, 'fe80:')) return true;
            return false;
        }

        return true;
    }
}
