<?php

declare(strict_types=1);

use Snicco\Monorepo\Package\PackageProvider;

require_once dirname(__DIR__) . '/vendor/autoload.php';

try {
    $package_provider = new PackageProvider(
        dirname(__DIR__),
        [
            dirname(__DIR__) . '/src/Snicco/Component',
            dirname(__DIR__) . '/src/Snicco/Middleware',
            dirname(__DIR__) . '/src/Snicco/Bridge',
            dirname(__DIR__) . '/src/Snicco/Bundle',
        ],
    );

    $packages = json_encode($package_provider->getAll(), JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}

echo $packages;
exit(0);
