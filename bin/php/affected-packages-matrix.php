<?php

declare(strict_types=1);

use Snicco\Monorepo\Package\Package;
use Snicco\Monorepo\SniccoWPPackageProvider;
use Webmozart\Assert\Assert;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    $input = $argv;
    array_shift($input);
    $diff = $input;

    Assert::allString($diff);

    $package_provider = SniccoWPPackageProvider::create();
    $packages = $package_provider->getAffected($diff);

    $codeception = $packages->filter(fn (Package $package): bool => $package->usesCodeception());
    $phpunit = $packages->filter(fn (Package $package): bool => $package->usesPHPUnit());

    $matrix = [
        'phpunit' => $phpunit,
        'codeception' => $codeception,
    ];

    $packages = json_encode($matrix, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

echo $packages;
exit(0);
