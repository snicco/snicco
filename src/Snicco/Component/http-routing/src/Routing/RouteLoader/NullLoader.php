<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\RouteLoader;

final class NullLoader implements RouteLoader
{

    public function loadRoutesIn(array $directories): void
    {
        //
    }

    public function loadApiRoutesIn(array $directories): void
    {
        //
    }
}