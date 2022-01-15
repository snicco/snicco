<?php

declare(strict_types=1);

namespace Tests\Session\integration\Drivers;

use Tests\Session\SessionDriverTest;
use Snicco\Session\Contracts\SessionClock;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Drivers\ArraySessionDriver;

final class ArraySessionDriverTest extends SessionDriverTest
{
    
    protected function createDriver(SessionClock $clock) :SessionDriver
    {
        return new ArraySessionDriver($clock);
    }
    
}