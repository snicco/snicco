<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit\fixtures;

use function trigger_error;

use const E_USER_DEPRECATED;

final class RoutingBundleTestController
{

    public function __invoke(): string
    {
        return self::class;
    }

    /**
     * @psalm-suppress UnusedVariable
     * @psalm-suppress EmptyArrayAccess
     * @psalm-suppress MixedAssignment
     */
    public function triggerNotice(): string
    {
        $arr = [];
        $foo = $arr[1];

        return self::class;
    }

    public function triggerDeprecation(): string
    {
        trigger_error(self::class, E_USER_DEPRECATED);
        return self::class;
    }

}