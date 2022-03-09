<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\fixtures;

use function trigger_error;

use const E_USER_DEPRECATED;
use const E_USER_NOTICE;

final class RoutingBundleTestController
{
    public function __invoke(): string
    {
        return self::class;
    }

    public function triggerNotice(): string
    {
        trigger_error(self::class, E_USER_NOTICE);
        return self::class;
    }

    public function triggerDeprecation(): string
    {
        @trigger_error(self::class, E_USER_DEPRECATED);
        return self::class;
    }
}
