<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\helpers;

use Pimple\Psr11\Container;
use Psr\Container\ContainerInterface;

trait CreatePsrTestContainer
{
    
    public function createContainer() :ContainerInterface
    {
        return new Container(new \Pimple\Container());
    }
    
}