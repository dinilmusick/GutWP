<?php

declare(strict_types=1);

namespace GutWP\Urls;

/** Portable, provider-independent path and HTTPS API URL safety. */
final class PathSafety {
    private const MAX_DECODING_LAYERS = 8;

    /** Reject traversal, separators, controls and invalid encodings at every layer. */
    private static function validateSegment(string $segment, bool $allowEncodedSlash = false): void {
        $value = $segment;
        for ($depth = 0; $depth < self::MAX_DECODING_LAYERS; $depth++) {
            if (preg_match('/%(?![0-9a-f]{2})/i', $value)) {
                throw new \InvalidArgumentException('Path contains invalid percent encoding');
            }
            $value = rawurldecode($value);
            if (preg_match('//u', $value) !== 1) {
                throw new \InvalidArgumentException('Path contains invalid UTF-8 encoding');
            }
            $pieces = explode('/', $value);
            if (in_array('.', $pieces, true) || in_array('..', $pieces, true)
                || preg_match('/[\\\\?#\x00-\x1f\x7f]/', $value)
                || (!$allowEncodedSlash && str_contains($value, '/'))) {
                throw new \InvalidArgumentException('Unsafe relative path segment');
            }
            if (!preg_match('/%[0-9a-f]{2}/i', $value)) return;
        }
        throw new \InvalidArgumentException('Path is too deeply encoded to verify safely');
    }

    public static function normalizeStoragePath(string $path = ''): string {
        if (preg_match('/[\\\\?#\x00-\x1f\x7f]/', $path) || preg_match('//u', $path) !== 1) {
            throw new \InvalidArgumentException('Invalid storage path');
        }
        $trimmed = trim($path, '/');
        if ($trimmed === '') return '';
        $parts = array_values(array_filter(explode('/', $trimmed), static fn($part) => $part !== ''));
        foreach ($parts as $part) self::validateSegment($part);
        return implode('/', $parts);
    }

    public static function encodeSafePathSegments(string $path): string {
        $normalized = self::normalizeStoragePath($path);
        if ($normalized === '') {
            throw new \InvalidArgumentException('A nonempty relative path is required');
        }
        return implode('/', array_map('rawurlencode', explode('/', $normalized)));
    }

    public static function resolveTrustedApiUrl(string $origin, string $path): string {
        $base = parse_url($origin);
        if ($base === false || !is_array($base)
            || strtolower((string) ($base['scheme'] ?? '')) !== 'https'
            || empty($base['host'])
            || isset($base['user']) || isset($base['pass'])
            || !in_array($base['path'] ?? '/', ['', '/'], true)
            || isset($base['query']) || isset($base['fragment'])
            || filter_var($origin, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Trusted API origin must be a bare HTTPS origin');
        }
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//')
            || str_contains($path, '#') || str_contains($path, '\\')
            || preg_match('/[\x00-\x1f\x7f]/', $path)) {
            throw new \InvalidArgumentException('Only trusted-origin relative API paths are permitted');
        }
        $pathname = explode('?', $path, 2)[0];
        foreach (explode('/', trim($pathname, '/')) as $segment) {
            if ($segment !== '') self::validateSegment($segment, true);
        }
        $target = rtrim($origin, '/') . $path;
        $resolved = parse_url($target);
        if ($resolved === false || strtolower((string) ($resolved['scheme'] ?? '')) !== 'https'
            || strtolower((string) ($resolved['host'] ?? '')) !== strtolower((string) $base['host'])
            || ($resolved['port'] ?? 443) !== ($base['port'] ?? 443)
            || isset($resolved['user']) || isset($resolved['pass']) || isset($resolved['fragment'])) {
            throw new \InvalidArgumentException('API path escaped its trusted origin');
        }
        return $target;
    }
}
