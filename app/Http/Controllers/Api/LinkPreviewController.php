<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LinkPreviewController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        $url = $validated['url'];

        try {
            $response = Http::timeout(5)->get($url);

            if (!$response->successful()) {
                return response()->json([
                    'title' => null,
                    'description' => null,
                    'image' => null,
                    'url' => $url,
                ]);
            }

            $html = $response->body();

            return response()->json([
                'title' => $this->parseOgTag($html, 'og:title'),
                'description' => $this->parseOgTag($html, 'og:description'),
                'image' => $this->parseOgTag($html, 'og:image'),
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'title' => null,
                'description' => null,
                'image' => null,
                'url' => $url,
            ]);
        }
    }

    private function parseOgTag(string $html, string $property): ?string
    {
        $pattern = '/<meta\s+property=["\']' . preg_quote($property, '/') . '["\']\s+content=["\']([^"\']*)["\'][^>]*>/i';

        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        // Also try reversed order (content before property)
        $pattern = '/<meta\s+content=["\']([^"\']*)["\']' . '\s+property=["\']' . preg_quote($property, '/') . '["\']\s*[^>]*>/i';

        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
