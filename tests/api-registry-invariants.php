<?php
declare(strict_types=1);

// Verifies custom-first registration without WordPress or Composer.
require_once __DIR__ . '/../src/Api/ApiTypes.php';
require_once __DIR__ . '/../src/Api/ApiRegistry.php';
require_once __DIR__ . '/../src/Arrays/Arrays.php';
require_once __DIR__ . '/../src/Api/FodRuntime.php';

use GutWP\Api\ApiDefinition;
use GutWP\Api\ApiParameter;
use GutWP\Api\ApiRegistry;
use GutWP\Api\FodRuntime;

function verifyRegistry(bool $ok, string $reason): void {
    if (!$ok) throw new RuntimeException($reason);
}
function rejectsRegistry(callable $fn, string $reason): void {
    try {
        $fn();
    } catch (LogicException $e) {
        return;
    }
    throw new RuntimeException($reason);
}

// This is intentionally the first registry call.
$extension = new ApiDefinition(
    'extension.json.encode', 'encode', 'json', 'Extension API',
    [new ApiParameter('value', 'Input to encode', true)]
);
ApiRegistry::register($extension);
verifyRegistry(ApiRegistry::has('gutwp.arrays.chunk'), 'Built-ins must load after custom-first registration');
verifyRegistry(ApiRegistry::has('extension.json.encode'), 'Extension must be retained');
verifyRegistry(ApiRegistry::get('gutwp.arrays.chunk')->category === 'arrays', 'Canonical definition preserved');
verifyRegistry(
    FodRuntime::executeGutApi('gutwp.arrays.chunk', ['values' => [1, 2, 3], 'size' => 2]) === [[1, 2], [3]],
    'Built-in FOD executor still available'
);
// Idempotent calls do not duplicate a definition.
ApiRegistry::register($extension);
ApiRegistry::register(new ApiDefinition(
    'extension.json.encode', 'encode', 'json', 'Extension API',
    [new ApiParameter('value', 'Input to encode', true)]
));
rejectsRegistry(static fn() => ApiRegistry::register(new ApiDefinition(
    'extension.json.encode', 'encode', 'json', 'Different ownership'
)), 'Conflicting extension ID must fail');
rejectsRegistry(static fn() => ApiRegistry::register(new ApiDefinition(
    'gutwp.arrays.chunk', 'chunk', 'arrays', 'Forged canonical definition'
)), 'Canonical API ID must not be shadowed');
$ids = array_map(static fn(ApiDefinition $api) => $api->id, ApiRegistry::all());
verifyRegistry(count(array_filter($ids, static fn($id) => $id === 'gutwp.arrays.chunk')) === 1, 'No duplicate canonical API');
verifyRegistry(count(array_filter($ids, static fn($id) => $id === 'extension.json.encode')) === 1, 'No duplicate extension API');
echo "GutWP API registry canonical initialization and collision regression: PASS\n";
