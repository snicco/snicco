<?php

declare(strict_types=1);

use Snicco\Component\StrArr\Str;
use Snicco\Monorepo\Package\Package;
use Snicco\Monorepo\SniccoWPPackageProvider;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    $package_provider = SniccoWPPackageProvider::create();

    $packages = $package_provider->getAll();

    $packages = $packages->filter(function (Package $package): bool {
        return !Str::contains($package->name, 'eloquent');
    });

    $packages = json_encode($packages, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}

echo $packages;
exit(0);
