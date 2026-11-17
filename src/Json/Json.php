<?php
declare(strict_types=1);

namespace GutWP\Json;

final class Json {
    /**
     * Return actual JSON or fail explicitly; invalid input must never silently
     * become the valid JSON literal "null".
     */
    public static function encode(mixed $value): string {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if (function_exists('wp_json_encode')) {
            $encoded = wp_json_encode($value, $flags);
            if (!is_string($encoded)) {
                throw new \JsonException('WordPress JSON encoding failed: ' . json_last_error_msg(), json_last_error());
            }
            return $encoded;
        }
        return json_encode($value, $flags | JSON_THROW_ON_ERROR);
    }

    public static function decode(string $json, mixed $default = null): mixed {
        $value = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $value : $default;
    }
}
