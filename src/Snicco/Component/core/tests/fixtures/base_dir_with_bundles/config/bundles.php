<?php

declare(strict_types=1);

use Snicco\Component\Core\Environment;
use Snicco\Component\Core\Tests\fixtures\bundles\Bundle2;
use Snicco\Component\Core\Tests\fixtures\bundles\Bundle1;
use Snicco\Component\Core\Tests\fixtures\bundles\AllEnvBundle;
use Snicco\Component\Core\Tests\fixtures\bundles\BundleProduction;
use Snicco\Component\Core\Tests\fixtures\bundles\BundleAssertsMethodOrder;

return [
    BundleProduction::class => [Environment::PROD => true],
    AllEnvBundle::class => ['all' => true],
    Bundle1::class => ['all' => true],
    Bundle2::class => ['all' => true],
    BundleAssertsMethodOrder::class => ['all' => true],
];