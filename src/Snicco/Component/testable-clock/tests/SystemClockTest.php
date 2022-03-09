<?php

declare(strict_types=1);


namespace Snicco\Component\TestableClock\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Snicco\Component\TestableClock\SystemClock;

use function date_default_timezone_get;
use function date_default_timezone_set;
use function usleep;

final class SystemClockTest extends TestCase
{
    private string $current_timezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->current_timezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->current_timezone);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function the_default_timezone_is_used_by_default(): void
    {
        date_default_timezone_set('europe/berlin');

        $clock = new SystemClock();

        $this->assertEquals($clock, new SystemClock(new DateTimeZone('europe/berlin')));
    }

    /**
     * @test
     */
    public function time_behaves_as_expected(): void
    {
        $timezone = date_default_timezone_get();
        $clock = new SystemClock(new DateTimeZone($timezone));

        $earlier = new DateTimeImmutable('now', new DateTimeZone($timezone));
        $earlier_ts = $earlier->getTimestamp();

        usleep(10000);

        $now = $clock->currentTime();
        $now_ts = $clock->currentTimestamp();

        $later = new DateTimeImmutable('now', new DateTimeZone($timezone));
        $later_ts = $later->getTimestamp();

        $this->assertGreaterThanOrEqual($now, $later);
        $this->assertGreaterThanOrEqual($now_ts, $later_ts);

        $this->assertLessThanOrEqual($now, $earlier);
        $this->assertLessThanOrEqual($now_ts, $earlier_ts);
    }

    /**
     * @test
     */
    public function a_custom_timezone_can_be_used(): void
    {
        $berlin = new DateTimeZone('europe/berlin');
        $berlin_clock = new SystemClock($berlin);

        $bangkok = new DateTimeZone('asia/bangkok');
        $bangkok_clock = new SystemClock($bangkok);

        $this->assertNotSame($berlin_clock->currentTime()->getOffset(), $bangkok_clock->currentTime()->getOffset());
    }

    /**
     * @test
     */
    public function test_from_utc(): void
    {
        date_default_timezone_set('europe/berlin');
        $clock = SystemClock::fromUTC();

        $this->assertNotEquals($clock, new SystemClock(new DateTimeZone('europe/berlin')));
        $this->assertEquals($clock, new SystemClock(new DateTimeZone('UTC')));
    }
}
