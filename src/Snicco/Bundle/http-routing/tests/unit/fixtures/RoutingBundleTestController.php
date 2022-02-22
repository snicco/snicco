<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit\fixtures;

final class RoutingBundleTestController
{

    public function __invoke(): string
    {
        return self::class;
    }

}