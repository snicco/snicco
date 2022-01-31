<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\Drivers;

use PHPUnit\Framework\TestCase;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Testing\SessionDriverTests;

final class InMemoryDriverTest extends TestCase
{
    
    use SessionDriverTests;
    
    protected function createDriver(Clock $clock) :SessionDriver
    {
        return new InMemoryDriver($clock);
    }
    
}