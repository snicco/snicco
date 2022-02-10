<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\Route;

abstract class RouteCollection implements Routes
{
    abstract public function add(Route $route): void;
}