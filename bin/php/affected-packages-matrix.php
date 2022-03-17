<?php

declare(strict_types=1);

use Snicco\Monorepo\Package\Package;
use Snicco\Monorepo\SniccoWPPackageProvider;
use Webmozart\Assert\Assert;

$root_dir = dirname(__DIR__, 2);

require_once $root_dir . '/vendor/autoload.php';

try {
    $input = $argv;
    array_shift($input);
    $diff = $input;
    
    Assert::allString($diff);

    $package_provider = SniccoWPPackageProvider::create();
    $packages = $package_provider->getAffected($diff);

    $codeception = $packages->filter(function (Package $package): bool {
        return $package->usesCodeception();
    });
    $phpunit = $packages->filter(function (Package $package): bool {
        return $package->usesPHPUnit();
    });

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