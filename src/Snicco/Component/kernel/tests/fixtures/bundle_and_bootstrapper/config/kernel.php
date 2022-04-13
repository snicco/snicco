<?php

declare(strict_types=1);

use Snicco\Component\Kernel\Tests\Bootstrap2;
use Snicco\Component\Kernel\Tests\BundleInfo;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    'bundles' => [
        Environment::ALL => [BundleInfo::class],
    ],

    'bootstrappers' => [Bootstrap2::class],
];
