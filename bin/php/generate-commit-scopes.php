<?php

declare(strict_types=1);

use Snicco\Monorepo\Package\PackageProvider;

require_once dirname(__DIR__) . '/vendor/autoload.php';


try {
    $scopes = [
        'monorepo',
    ];

    $package_provider = new PackageProvider(
        dirname(__DIR__),
        [
            dirname(__DIR__) . '/src/Snicco/Component',
            dirname(__DIR__) . '/src/Snicco/Middleware',
            dirname(__DIR__) . '/src/Snicco/Bridge',
            dirname(__DIR__) . '/src/Snicco/Bundle',
        ],
    );

    $packages = $package_provider->getAll();

    foreach ($packages as $package) {
        $scopes[] = $package->name;
    }

    $json = json_encode($scopes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    $res = file_put_contents($f = dirname(__DIR__) . '/commit-scopes.json', $json);
    Webmozart\Assert\Assert::notFalse($res, 'Could not update commit scopes.');
    echo sprintf("Updated commit scopes at [%s]\n", $f);
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}

exit(0);