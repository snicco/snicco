<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\RouteLoading;

interface RouteLoader
{
    /**
     * @param string[] $directories
     */
    public function loadRoutesIn(array $directories): void;

    /**
     * @param string[] $directories
     */
    public function loadApiRoutesIn(array $directories): void;
}