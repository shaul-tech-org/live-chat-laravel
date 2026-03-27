<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class XssSanitizer
{
    /**
     * Dangerous HTML tags/patterns to strip from user input.
     *
     * Note: Output escaping (Blade {{ }}) is the primary XSS defense.
     * This middleware acts as defense-in-depth by removing obviously
     * dangerous payloads before they reach the database.
     */
    private const DANGEROUS_PATTERNS = [
        '/<script\b[^>]*>.*?<\/script>/is',
        '/<iframe\b[^>]*>.*?<\/iframe>/is',
        '/<object\b[^>]*>.*?<\/object>/is',
        '/<embed\b[^>]*>.*?<\/embed>/is',
        '/<applet\b[^>]*>.*?<\/applet>/is',
        '/<meta\b[^>]*>/is',
        '/<link\b[^>]*>/is',
        '/<style\b[^>]*>.*?<\/style>/is',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:[^,]*;base64/i',
        '/on\w+\s*=\s*["\'][^"\']*["\']/i',
        '/on\w+\s*=\s*[^\s>]*/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = $this->stripDangerousTags($value);
            }
        });
        $request->merge($input);

        return $next($request);
    }

    /**
     * Strip dangerous HTML tags and event handlers from the value.
     * Does NOT apply htmlspecialchars — Blade {{ }} handles output escaping.
     */
    private function stripDangerousTags(string $value): string
    {
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        return $value;
    }
}
