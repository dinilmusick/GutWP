<?php
declare(strict_types=1);

namespace GutWP\Api;

require_once __DIR__ . '/ApiTypes.php';

final class ApiRegistry {
    /** @var array<string, ApiDefinition> */
    private static array $definitions = [];
    private static bool $defaultsLoaded = false;

    /** Always register built-ins before custom definitions, regardless of call order. */
    private static function ensureDefaults(): void {
        if (self::$defaultsLoaded) return;
        self::$defaultsLoaded = true;
        self::registerDefaults();
    }

    public static function all(): array {
        self::ensureDefaults();
        return array_values(self::$definitions);
    }

    public static function get(string $id): ApiDefinition {
        self::ensureDefaults();
        return self::$definitions[$id] ?? throw new \InvalidArgumentException("Unknown GutWP API '$id'.");
    }

    public static function has(string $id): bool {
        self::ensureDefaults();
        return isset(self::$definitions[$id]);
    }

    public static function register(ApiDefinition $def): void {
        self::ensureDefaults();
        if (isset(self::$definitions[$def->id])) {
            // Repeated identical registrations are safe; changed ownership is not.
            if (self::$definitions[$def->id] == $def) return;
            throw new \LogicException("GutWP API '{$def->id}' is already registered.");
        }
        self::$definitions[$def->id] = $def;
    }

    private static function registerDefaults(): void {
        $defs = [
            // Arrays
            ['gutwp.arrays.compact', 'compact', 'arrays', 'Compact nulls from array', ['values']],
            ['gutwp.arrays.unique', 'unique', 'arrays', 'Unique values from array', ['values']],
            ['gutwp.arrays.chunk', 'chunk', 'arrays', 'Chunk array into sub-arrays', ['values', 'size']],
            ['gutwp.arrays.flatten', 'flatten', 'arrays', 'Flatten nested array 1 level', ['values']],
            ['gutwp.arrays.last', 'last', 'arrays', 'Get last element of array', ['values']],
            ['gutwp.arrays.first', 'first', 'arrays', 'Get first element of array', ['values']],

            // Strings
            ['gutwp.strings.isBlank', 'isBlank', 'strings', 'Check if string is blank or empty', ['value']],
            ['gutwp.strings.isEmail', 'isEmail', 'strings', 'Check if string is valid email', ['value']],
            ['gutwp.strings.capitalize', 'capitalize', 'strings', 'Capitalize first letter of string', ['value']],
            ['gutwp.strings.camelCase', 'camelCase', 'strings', 'Convert string to camelCase', ['value']],
            ['gutwp.strings.kebabCase', 'kebabCase', 'strings', 'Convert string to kebab-case', ['value']],
            ['gutwp.strings.snakeCase', 'snakeCase', 'strings', 'Convert string to snake_case', ['value']],
            ['gutwp.strings.truncate', 'truncate', 'strings', 'Truncate string to length', ['value', 'length']],

            // Numbers
            ['gutwp.numbers.clamp', 'clamp', 'numbers', 'Clamp number within range', ['value', 'min', 'max']],
            ['gutwp.numbers.between', 'between', 'numbers', 'Check if number is within range', ['value', 'min', 'max']],
            ['gutwp.numbers.roundTo', 'roundTo', 'numbers', 'Round number to precision', ['value', 'precision']],
            ['gutwp.numbers.sum', 'sum', 'numbers', 'Sum all elements of array', ['values']],

            // Objects
            ['gutwp.objects.pick', 'pick', 'objects', 'Pick specified keys from object/array', ['value', 'keys']],
            ['gutwp.objects.omit', 'omit', 'objects', 'Omit specified keys from object/array', ['value', 'keys']],

            // URLs
            ['gutwp.urls.isUrl', 'isUrl', 'urls', 'Validate if string is URL', ['value']],
            ['gutwp.urls.joinUrl', 'joinUrl', 'urls', 'Join URL path segments', ['base', 'paths']],

            // JSON
            ['gutwp.json.encode', 'encode', 'json', 'Encode value to JSON string', ['value']],
            ['gutwp.json.decode', 'decode', 'json', 'Decode JSON string to array', ['json']],
        ];

        foreach ($defs as [$id, $name, $cat, $desc, $params]) {
            $paramObjects = array_map(
                static fn(string $p) => new ApiParameter($p, "Parameter $p", true),
                $params
            );
            self::register(new ApiDefinition(
                $id, $name, $cat, $desc, $paramObjects, [], new ApiSemantics()
            ));
        }
    }
}
