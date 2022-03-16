<?php

declare(strict_types=1);

use Snicco\Monorepo\SniccoWPPackageProvider;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';


try {
    $scopes = [
        'monorepo',
    ];

    $package_provider = SniccoWPPackageProvider::create();

    $packages = $package_provider->getAll();

    foreach ($packages as $package) {
        $scopes[] = $package->name;
    }

    $json = json_encode($scopes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    $res = file_put_contents($f = dirname(__DIR__, 2) . '/commit-scopes.json', (string)$json);
    Webmozart\Assert\Assert::notFalse($res, 'Could not update commit scopes.');
    echo sprintf("Updated commit scopes at [%s]\n", $f);
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}

exit(0);