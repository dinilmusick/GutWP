<?php
namespace GutWP\Numbers;

final class Numbers {
    public static function clamp(int|float $value,int|float $min,int|float $max): int|float {
        return max($min,min($max,$value));
    }
    public static function between(int|float $value,int|float $min,int|float $max): bool {
        return $value>=$min && $value<=$max;
    }
}