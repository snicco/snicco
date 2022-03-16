<?php

declare(strict_types=1);

use Snicco\Monorepo\SniccoWPPackageProvider;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    $package_provider = SniccoWPPackageProvider::create();

    $packages = json_encode($package_provider->getAll(), JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}

echo $packages;
exit(0);
