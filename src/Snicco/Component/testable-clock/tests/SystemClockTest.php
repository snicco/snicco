<?php

declare(strict_types=1);

namespace Snicco\Component\TestableClock\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Snicco\Component\TestableClock\SystemClock;

use function sleep;

final class SystemClockTest extends TestCase
{
    
    /** @test */
    public function the_current_time_defaults_to_the_system_time()
    {
        $clock = new SystemClock();
        
        $this->assertEqualsWithDelta(
            $d = new DateTimeImmutable('now'),
            $clock->currentTime(),
            0.005
        );
        $this->assertEqualsWithDelta($d->getTimestamp(), $clock->currentTimestamp(), 0.005);
    }
    
    /** @test */
    public function the_clock_does_not_stay_frozen()
    {
        $clock = new SystemClock();
        $ts1 = $clock->currentTimestamp();
        
        sleep(1);
        
        $ts2 = $clock->currentTimestamp();
        $this->assertEqualsWithDelta($ts1 + 1, $ts2, 0.005);
    }
    
}