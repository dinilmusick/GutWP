<?php
namespace GutWP\Arrays;

final class Arrays {
    public static function isList(array $value): bool { return array_is_list($value); }
    public static function first(array $value,mixed $default=null): mixed { foreach($value as $item) return $item; return $default; }
    public static function get(array $value,string|int $key,mixed $default=null): mixed { return $value[$key]??$default; }
}