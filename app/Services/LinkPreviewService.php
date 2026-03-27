<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LinkPreviewService
{
    public function fetch(string $url): array
    {
        $empty = [
            'title' => null,
            'description' => null,
            'image' => null,
            'url' => $url,
        ];

        try {
            $response = Http::timeout(5)->get($url);

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
        } catch (\Exception) {
            return $empty;
        }
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
