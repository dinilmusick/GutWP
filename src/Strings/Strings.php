<?php
namespace GutWP\Strings;

use Symfony\Component\String\Slugger\AsciiSlugger;

final class Strings {
    public static function nonEmpty(string $value): bool { return trim($value)!==''; }
    public static function slug(string $value): string { return (new AsciiSlugger())->slug($value)->lower()->toString(); }
    public static function startsWith(string $value,string $prefix): bool { return str_starts_with($value,$prefix); }
    public static function endsWith(string $value,string $suffix): bool { return str_ends_with($value,$suffix); }
}