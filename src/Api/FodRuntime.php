<?php
declare(strict_types=1);

namespace GutWP\Api;

use GutWP\Arrays\Arrays;
use GutWP\Strings\Strings;
use GutWP\Numbers\Numbers;
use GutWP\Objects\Objects;
use GutWP\Urls\Urls;
use GutWP\Json\Json;

final class FodRuntime {
    public static function executeGutApi(string $apiId, array $input): mixed {
        $api = ApiRegistry::get($apiId);
        // The API registry's required-parameter contract is executable.
        // Do not turn omitted workflow input into a fabricated default.
        foreach ($api->parameters as $param) {
            if ($param->required && !array_key_exists($param->name, $input)) {
                throw new \InvalidArgumentException("Missing required input '{$param->name}' for GutWP API '{$apiId}'.");
            }
        }

        // Dispatch by canonical API ID, not by a user-registered display name.
        // Custom definitions cannot impersonate built-in FOD executors.
        return match ($api->id) {
            'gutwp.arrays.compact'       => Arrays::compact($input['values'] ?? []),
            'gutwp.arrays.unique'        => Arrays::unique($input['values'] ?? []),
            'gutwp.arrays.chunk'         => Arrays::chunk($input['values'] ?? [], (int) ($input['size'] ?? 1)),
            'gutwp.arrays.flatten'       => Arrays::flatten($input['values'] ?? []),
            'gutwp.arrays.last'          => Arrays::last($input['values'] ?? []),
            'gutwp.arrays.first'         => Arrays::first($input['values'] ?? []),
            'gutwp.strings.isBlank'       => Strings::isBlank($input['value'] ?? null),
            'gutwp.strings.isEmail'       => Strings::isEmail((string) ($input['value'] ?? '')),
            'gutwp.strings.capitalize'    => Strings::capitalize((string) ($input['value'] ?? '')),
            'gutwp.strings.camelCase'     => Strings::camelCase((string) ($input['value'] ?? '')),
            'gutwp.strings.kebabCase'     => Strings::kebabCase((string) ($input['value'] ?? '')),
            'gutwp.strings.snakeCase'     => Strings::snakeCase((string) ($input['value'] ?? '')),
            'gutwp.strings.truncate'      => Strings::truncate((string) ($input['value'] ?? ''), (int) ($input['length'] ?? 100)),
            'gutwp.numbers.clamp'         => Numbers::clamp($input['value'] ?? 0, $input['min'] ?? 0, $input['max'] ?? 100),
            'gutwp.numbers.between'       => Numbers::between($input['value'] ?? 0, $input['min'] ?? 0, $input['max'] ?? 100),
            'gutwp.numbers.roundTo'       => Numbers::roundTo($input['value'] ?? 0, (int) ($input['precision'] ?? 0)),
            'gutwp.numbers.sum'           => Numbers::sum($input['values'] ?? []),
            'gutwp.objects.pick'          => Objects::pick($input['value'] ?? [], $input['keys'] ?? []),
            'gutwp.objects.omit'          => Objects::omit($input['value'] ?? [], $input['keys'] ?? []),
            'gutwp.urls.isUrl'         => Urls::isUrl((string) ($input['value'] ?? '')),
            'gutwp.urls.joinUrl'       => Urls::joinUrl((string) ($input['base'] ?? ''), ...((array) ($input['paths'] ?? []))),
            'gutwp.json.encode'        => Json::encode($input['value'] ?? null),
            'gutwp.json.decode'        => Json::decode((string) ($input['json'] ?? '{}')),
            default         => throw new \RuntimeException("GutWP API '$apiId' has metadata but no embedded runtime executor."),
        };
    }
}
