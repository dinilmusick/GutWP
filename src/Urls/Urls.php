<?php
namespace GutWP\Urls;

use League\Uri\Uri;

final class Urls {
    public static function parse(string $url): array {
        $uri=Uri::new($url);
        return ['scheme'=>$uri->getScheme(),'host'=>$uri->getHost(),'path'=>$uri->getPath(),'query'=>$uri->getQuery()];
    }
    public static function isAbsolute(string $url): bool { return Uri::new($url)->getHost()!==null; }
}