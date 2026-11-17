<?php
declare(strict_types=1);

// Mimic WordPress' encoding hook in a separate process so plain PHP fallback
// and WordPress failure modes are tested independently.
function wp_json_encode(mixed $value, int $flags = 0): string|false {
    return json_encode($value, $flags);
}
require_once __DIR__ . '/../src/Json/Json.php';

use GutWP\Json\Json;

if (Json::encode(['ok' => true]) !== '{"ok":true}') {
    throw new RuntimeException('WordPress JSON success path changed.');
}
try {
    Json::encode(INF);
} catch (JsonException $e) {
    echo "GutWP WordPress JSON failure propagation: PASS\n";
    exit(0);
}
throw new RuntimeException('WordPress JSON encoding failure must throw, not become null.');
