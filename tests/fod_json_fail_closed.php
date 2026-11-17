<?php
declare(strict_types=1);

// Executable without WordPress or Composer: test real JSON/FOD failure paths.
require_once __DIR__ . '/../src/Api/ApiTypes.php';
require_once __DIR__ . '/../src/Api/ApiRegistry.php';
require_once __DIR__ . '/../src/Arrays/Arrays.php';
require_once __DIR__ . '/../src/Strings/Strings.php';
require_once __DIR__ . '/../src/Numbers/Numbers.php';
require_once __DIR__ . '/../src/Objects/Objects.php';
require_once __DIR__ . '/../src/Urls/Urls.php';
require_once __DIR__ . '/../src/Json/Json.php';
require_once __DIR__ . '/../src/Api/FodRuntime.php';

use GutWP\Api\ApiDefinition;
use GutWP\Api\ApiParameter;
use GutWP\Api\ApiRegistry;
use GutWP\Api\FodRuntime;
use GutWP\Json\Json;

function check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function rejects(callable $action, string $expected): void {
    try {
        $action();
    } catch (Throwable $error) {
        check($error instanceof $expected, 'Expected ' . $expected . ', got ' . get_class($error));
        return;
    }
    throw new RuntimeException('Expected ' . $expected . ' but execution succeeded.');
}

check(Json::encode(null) === 'null', 'Valid JSON null must remain valid');
check(Json::encode(['ok' => true]) === '{"ok":true}', 'Ordinary JSON encoding works without WP');
check(Json::decode('{"ok":true}') === ['ok' => true], 'JSON decode round trip');
rejects(static fn() => Json::encode(INF), JsonException::class);
rejects(static fn() => Json::encode("\xB1"), JsonException::class);

check(FodRuntime::executeGutApi('gutwp.arrays.chunk', ['values' => [1, 2, 3], 'size' => 2]) === [[1, 2], [3]], 'Canonical chunk executes');
check(FodRuntime::executeGutApi('gutwp.json.encode', ['value' => null]) === 'null', 'Canonical JSON null executes');
rejects(static fn() => FodRuntime::executeGutApi('gutwp.arrays.chunk', ['values' => [1, 2, 3]]), InvalidArgumentException::class);
rejects(static fn() => FodRuntime::executeGutApi('gutwp.arrays.chunk', ['size' => 2]), InvalidArgumentException::class);
rejects(static fn() => FodRuntime::executeGutApi('gutwp.json.encode', []), InvalidArgumentException::class);
rejects(static fn() => FodRuntime::executeGutApi('gutwp.arrays.chunk', ['values' => [], 'size' => 0]), RangeException::class);

// A custom registry definition with a built-in display name may not execute
// under another capability's authority.
ApiRegistry::register(new ApiDefinition(
    'custom.json.encode', 'encode', 'json', 'Untrusted alias',
    [new ApiParameter('value', 'Value to encode', true)]
));
rejects(static fn() => FodRuntime::executeGutApi('custom.json.encode', ['value' => 'secret']), RuntimeException::class);
echo "GutWP JSON and FOD fail-closed regression: PASS\n";
