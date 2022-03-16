<?php

declare(strict_types=1);

use Snicco\Component\StrArr\Str;
use Snicco\Monorepo\SniccoWPPackageProvider;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    $package_provider = SniccoWPPackageProvider::create();

    $packages = $package_provider->getAll();

    $invalid = [];

    foreach ($packages as $package) {
        $rel_dir = $package->package_dir_rel;
        $name = $package->name;

        if (Str::startsWith($rel_dir, 'src/Snicco/Component')) {
            if (Str::containsAny($name, ['middleware', 'bundle', 'bridge'])) {
                $invalid[] = sprintf('Component package [%s] must not contain [middleware,bundle,bridge]', $name);
            }
        } elseif (Str::startsWith($rel_dir, 'src/Snicco/Bundle')) {
            if (! Str::endsWith($name, '-bundle')) {
                $invalid[] = sprintf('Bundle package [%s] must end with suffix "-bundle"', $name);
            }
        } elseif (Str::startsWith($rel_dir, 'src/Snicco/Middleware')) {
            if (! Str::endsWith($name, '-middleware')) {
                $invalid[] = sprintf('Middleware package [%s] must end with suffix "-middleware"', $name);
            }
        } elseif (Str::startsWith($rel_dir, 'src/Snicco/Bridge')) {
            if (! Str::endsWith($name, '-bridge')) {
                $invalid[] = sprintf('Bridge package [%s] must end with suffix "-bridge"', $name);
            }
        } else {
            throw new InvalidArgumentException(sprintf(
                'Invalid relative directory %s for package %s',
                $rel_dir,
                $name
            ));
        }
    }

    if ([] !== $invalid) {
        echo implode("\n", $invalid) . "\n";
        exit(1);
    }

    echo "All packages names are valid.\n";
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}

exit(0);
