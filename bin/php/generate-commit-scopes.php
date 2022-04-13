<?php

declare(strict_types=1);

use Snicco\Component\StrArr\Str;
use Snicco\Monorepo\SniccoPackageProvider;
use Webmozart\Assert\Assert;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    $extra_scopes = ['monorepo', '*'];
    $components = [];
    $middleware = [];
    $bundles = [];
    $bridge = [];
    $testing = [];

    $package_provider = SniccoPackageProvider::create();

    $packages = $package_provider->getAll();

    foreach ($packages as $package) {
        $name = $package->name;
        if (Str::endsWith($name, '-middleware')) {
            $middleware[] = $name;
        } elseif (Str::endsWith($name, '-bundle')) {
            $bundles[] = $name;
        } elseif (Str::endsWith($name, '-bridge')) {
            $bridge[] = $name;
        } elseif (Str::endsWith($name, '-testing')) {
            $testing[] = $name;
        } else {
            $components[] = $name;
        }
    }

    sort($components);
    sort($bridge);
    sort($bundles);
    sort($middleware);
    sort($testing);

    $merged = [...$extra_scopes, ...$components, ...$bridge, ...$bundles, ...$middleware, ...$testing];

    $json = json_encode($merged, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    $res = file_put_contents($f = dirname(__DIR__, 2) . '/commit-scopes.json', (string) $json);
    Assert::notFalse($res, 'Could not update commit scopes.');
    echo sprintf("Updated commit scopes at [%s]\n", $f);
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}

exit(0);
