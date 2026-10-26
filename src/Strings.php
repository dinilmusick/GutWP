<?php
namespace GutWP;
final class Strings { public static function isNonEmpty(mixed $value): bool { return is_string($value) && trim($value)!==''; } }
