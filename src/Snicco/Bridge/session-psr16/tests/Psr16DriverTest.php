<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionPsr16\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Component\TestableClock\Clock;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Snicco\Bridge\SessionPsr16\Psr16Driver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\Session\ValueObject\SerializedSessionData;

use function time;

final class Psr16DriverTest extends TestCase
{
    
    use SessionDriverTests;
    
    public function garbage_collection_works_for_old_sessions()
    {
        // Automatically
    }
    
    /** @test */
    public function garbage_collection_works_automatically()
    {
        $driver = new Psr16Driver(new ArrayCachePool(), 1);
        
        $driver->write('session1', SerializedSessionData::fromArray(['foo' => 'bar'], time()));
        
        sleep(1);
        
        $this->expectException(BadSessionID::class);
        
        $driver->read('session1');
    }
    
    protected function createDriver(Clock $clock) :SessionDriver
    {
        return new Psr16Driver(new ArrayCachePool(), 10);
    }
    
}