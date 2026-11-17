<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Urls/PathSafety.php';

use GutWP\Urls\PathSafety;

function assertSafe(bool $value, string $reason): void {
    if (!$value) throw new RuntimeException($reason);
}
function rejectUnsafe(callable $callback, string $reason): void {
    try { $callback(); }
    catch (InvalidArgumentException $error) { return; }
    throw new RuntimeException("$reason: unsafe input was accepted");
}

assertSafe(PathSafety::normalizeStoragePath('/my//photos/report 2026.txt/') === 'my/photos/report 2026.txt', 'safe normalization');
assertSafe(PathSafety::normalizeStoragePath('///') === '', 'root compatibility');
assertSafe(PathSafety::encodeSafePathSegments('documents/my report 2026.txt') ===
    'documents/my%20report%202026.txt', 'per-segment URL encoding');
assertSafe(PathSafety::encodeSafePathSegments('café/images') === 'caf%C3%A9/images', 'UTF-8 path encoding');
foreach ([
    'a/../b', 'a/./b', 'a/%2e%2e/b', 'a/%252e%252e/b',
    'a/%2Fetc', 'a/%252fetc', 'a/%5cserver', 'a/%253Fquery',
    'a?query=1', 'a#fragment', 'a\\backslash', "a\x00bad", "a\nline",
    'a/%', 'a/%G0', 'a/%FF'
] as $bad) {
    rejectUnsafe(fn() => PathSafety::normalizeStoragePath($bad), "storage path $bad");
}
rejectUnsafe(fn() => PathSafety::encodeSafePathSegments('/'), 'empty encoding');

$nested = '%2e%2e';
for ($i = 0; $i < 8; $i++) $nested = rawurlencode($nested);
rejectUnsafe(fn() => PathSafety::normalizeStoragePath('folder/' . $nested), 'deep encoding');

$origin = 'https://api.example.test:8443';
assertSafe(PathSafety::resolveTrustedApiUrl($origin, '/v1/projects%2Fteam?limit=10') ===
    $origin . '/v1/projects%2Fteam?limit=10', 'encoded slash and query permitted in trusted API path');
assertSafe(PathSafety::resolveTrustedApiUrl('https://api.example.test/', '/v1/items') ===
    'https://api.example.test/v1/items', 'bare origin trailing slash accepted');
assertSafe(PathSafety::resolveTrustedApiUrl('https://api.example.test', '/v1') ===
    'https://api.example.test/v1', 'default HTTPS port accepted');

foreach ([
    'http://api.example.test', 'https://user:password@api.example.test',
    'https://api.example.test/base', 'https://api.example.test?auth=1',
    'https://api.example.test#frag', 'javascript://api.example.test'
] as $unsafeOrigin) {
    rejectUnsafe(fn() => PathSafety::resolveTrustedApiUrl($unsafeOrigin, '/v1'),
        "origin $unsafeOrigin");
}
foreach ([
    '//attacker.test/x', 'https://attacker.test/x', '/a/../b',
    '/a/%2e%2e/b', '/a/%252e%252e/b', '/api/%2F../hidden',
    '/v1/%5Csecret', '/v1?x=1#fragment', '/v1\\host', "/v1\tbad",
    '/v1/%ZZ'
] as $unsafeRelative) {
    rejectUnsafe(fn() => PathSafety::resolveTrustedApiUrl($origin, $unsafeRelative),
        "trusted path $unsafeRelative");
}
echo "GutWP path safety parity tests: OK\n";
