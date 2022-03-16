<?php

declare(strict_types=1);

namespace Snicco\Monorepo;

use Snicco\Monorepo\Package\PackageProvider;

use function dirname;

final class SniccoWPPackageProvider
{
    public static function create(): PackageProvider
    {
        $root_dir = dirname(__DIR__, 2);

        return new PackageProvider(
            $root_dir,
            [
                $root_dir . '/src/Snicco/Component',
                $root_dir . '/src/Snicco/Middleware',
                $root_dir . '/src/Snicco/Bridge',
                $root_dir . '/src/Snicco/Bundle',
            ]
        );
    }
}
