<?php
namespace GutWP\Objects;

final class Objects {
    public static function get(object|array $value,string|int $key,mixed $default=null): mixed {
        if(is_array($value)) return $value[$key]??$default;
        return property_exists($value,(string)$key)?$value->{$key}:$default;
    }
    public static function has(object|array $value,string|int $key): bool {
        return is_array($value)?array_key_exists($key,$value):property_exists($value,(string)$key);
    }
}