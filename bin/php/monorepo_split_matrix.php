<?php

declare(strict_types=1);

use Snicco\Component\StrArr\Str;
use Snicco\Monorepo\Package\Package;
use Snicco\Monorepo\SniccoPackageProvider;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    $package_provider = SniccoPackageProvider::create();

    $packages = $package_provider->getAll();

    $packages = $packages->filter(fn (Package $package): bool => ! Str::contains($package->name, 'eloquent'));

    $packages = json_encode($packages, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}

echo $packages;
exit(0);
