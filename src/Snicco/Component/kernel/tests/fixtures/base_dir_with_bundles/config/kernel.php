<?php

declare(strict_types=1);

use Snicco\Component\Kernel\Tests\fixtures\bundles\AllEnvBundle;
use Snicco\Component\Kernel\Tests\fixtures\bundles\Bundle1;
use Snicco\Component\Kernel\Tests\fixtures\bundles\Bundle2;
use Snicco\Component\Kernel\Tests\fixtures\bundles\BundleAfterConfiguration;
use Snicco\Component\Kernel\Tests\fixtures\bundles\BundleAssertsMethodOrder;
use Snicco\Component\Kernel\Tests\fixtures\bundles\BundleProduction;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
    'bundles' => [
        Environment::ALL => [
            AllEnvBundle::class,
            Bundle1::class,
            Bundle2::class,
            BundleAssertsMethodOrder::class,
            BundleAfterConfiguration::class,
        ],

        Environment::PROD => [BundleProduction::class],
    ],

    'bootstrappers' => [],
];
