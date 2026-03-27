<?php

namespace App\Services;

use App\Exceptions\SsrfException;
use Illuminate\Support\Facades\Http;

class LinkPreviewService
{
    /**
     * Private/internal IPv4 CIDR ranges to block.
     */
    private const BLOCKED_IPV4_CIDRS = [
        '127.0.0.0/8',       // Loopback
        '10.0.0.0/8',        // Private Class A
        '172.16.0.0/12',     // Private Class B
        '192.168.0.0/16',    // Private Class C
        '169.254.0.0/16',    // Link-local
        '0.0.0.0/8',         // Current network
    ];

    /**
     * Blocked IPv6 addresses/prefixes.
     */
    private const BLOCKED_IPV6_PREFIXES = [
        '::1',       // Loopback
        'fc00::',    // Unique local (fc00::/7)
        'fd00::',    // Unique local (fc00::/7)
        'fe80::',    // Link-local
    ];

    public function fetch(string $url): array
    {
        $empty = [
            'title' => null,
            'description' => null,
            'image' => null,
            'url' => $url,
        ];

        try {
            $this->validateUrl($url);

            $response = Http::timeout(5)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'on_redirect' => function ($request, $response, $uri) {
                            $this->validateUrl((string) $uri);
                        },
                    ],
                ])
                ->get($url);

            if (!$response->successful()) {
                return $empty;
            }

            $html = $response->body();

            return [
                'title' => $this->parseOgTag($html, 'og:title'),
                'description' => $this->parseOgTag($html, 'og:description'),
                'image' => $this->parseOgTag($html, 'og:image'),
                'url' => $url,
            ];
        } catch (SsrfException) {
            return $empty;
        } catch (\Exception) {
            return $empty;
        }
    }

    /**
     * Validate that the URL does not target internal/private networks.
     *
     * @throws SsrfException
     */
    private function validateUrl(string $url): void
    {
        $parsed = parse_url($url);

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new SsrfException("Blocked non-HTTP(S) scheme: {$scheme}");
        }

        $host = $parsed['host'] ?? '';
        if ($host === '') {
            throw new SsrfException('No host in URL');
        }

        // Resolve DNS to get IP address
        $ip = $this->resolveHost($host);

        if ($this->isPrivateIp($ip)) {
            throw new SsrfException("Blocked request to private/internal IP: {$ip}");
        }
    }

    /**
     * Resolve a hostname to an IP address.
     *
     * @throws SsrfException
     */
    private function resolveHost(string $host): string
    {
        // If the host is already an IP, return it directly
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }

        $ip = gethostbyname($host);

        // gethostbyname returns the hostname if resolution fails
        if ($ip === $host) {
            throw new SsrfException("DNS resolution failed for host: {$host}");
        }

        return $ip;
    }

    /**
     * Check if an IP address belongs to a private/internal range.
     */
    private function isPrivateIp(string $ip): bool
    {
        // Check IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->isPrivateIpv6($ip);
        }

        // Check IPv4
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true; // Block unrecognized IP formats
        }

        foreach (self::BLOCKED_IPV4_CIDRS as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IPv6 address is private/internal.
     */
    private function isPrivateIpv6(string $ip): bool
    {
        $ip = strtolower($ip);

        // Exact match for loopback
        if ($ip === '::1') {
            return true;
        }

        // Check prefixes (fc00::/7 covers fc00:: and fd00::, fe80:: is link-local)
        $expandedIp = inet_ntop(inet_pton($ip));
        foreach (self::BLOCKED_IPV6_PREFIXES as $prefix) {
            $expandedPrefix = inet_ntop(inet_pton($prefix));
            if ($expandedPrefix === false) {
                continue;
            }
            // fc00::/7 — check first byte
            if ($prefix === 'fc00::' || $prefix === 'fd00::') {
                $ipBin = inet_pton($ip);
                $firstByte = ord($ipBin[0]);
                if (($firstByte & 0xFE) === 0xFC) {
                    return true;
                }
            } elseif (str_starts_with($expandedIp, substr($expandedPrefix, 0, strrpos($expandedPrefix, ':') + 1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IPv4 address is within a CIDR range.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $bits = (int) $bits;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private function parseOgTag(string $html, string $property): ?string
    {
        $pattern = '/<meta\s+property=["\']' . preg_quote($property, '/') . '["\']\s+content=["\']([^"\']*)["\'][^>]*>/i';

        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        $pattern = '/<meta\s+content=["\']([^"\']*)["\']' . '\s+property=["\']' . preg_quote($property, '/') . '["\']\s*[^>]*>/i';

        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
