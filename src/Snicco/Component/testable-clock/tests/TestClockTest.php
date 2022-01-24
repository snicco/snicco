<?php

declare(strict_types=1);

namespace Snicco\Component\TestableClock\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Snicco\Component\TestableClock\TestClock;

use function time;
use function usleep;

final class TestClockTest extends TestCase
{
    
    /** @test */
    public function the_current_time_defaults_to_the_system_time()
    {
        $clock = new TestClock();
        
        $this->assertEqualsWithDelta(new DateTimeImmutable('now'), $clock->currentTime(), 0.1);
        $this->assertEqualsWithDelta(time(), $clock->currentTimestamp(), 0.1);
    }
    
    /** @test */
    public function the_current_time_can_be_passed_as_a_constructor_argument()
    {
        $clock = new TestClock($time = new DateTimeImmutable('12-12-2020'));
        
        $this->assertNotEqualsWithDelta(time(), $clock->currentTimestamp(), 0.1);
        $this->assertNotEqualsWithDelta(
            new DateTimeImmutable('now'),
            $clock->currentTime(),
            0.1
        );
        
        $this->assertEqualsWithDelta($time, $clock->currentTime(), 0.1);
    }
    
    /** @test */
    public function the_clock_stays_frozen()
    {
        $clock = new TestClock();
        $ts1 = $clock->currentTimestamp();
        
        // 1.1s
        usleep(11 * 10 ** 5);
        
        $ts2 = $clock->currentTimestamp();
        $this->assertEquals($ts1, $ts2);
    }
    
    /** @test */
    public function the_clock_can_travel_into_the_future()
    {
        $clock = new TestClock();
        $ts1 = $clock->currentTimestamp();
        
        $clock->travelIntoFuture(100);
        
        $this->assertEquals($ts1 + 100, $clock->currentTimestamp());
    }
    
    /** @test */
    public function the_clock_can_travel_into_the_past()
    {
        $clock = new TestClock();
        $ts1 = $clock->currentTimestamp();
        
        $clock->travelIntoPast(100);
        
        $this->assertEquals($ts1 - 100, $clock->currentTimestamp());
    }
    
}