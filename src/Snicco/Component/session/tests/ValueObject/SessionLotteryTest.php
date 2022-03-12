<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\SessionLottery;

/**
 * @internal
 */
final class SessionLotteryTest extends TestCase
{
    /**
     * @test
     */
    public function test_exception_high_percentage(): void
    {
        $this->expectException(LogicException::class);

        new SessionLottery(101);
    }

    /**
     * @test
     */
    public function test_exception_low_percentage(): void
    {
        $this->expectException(LogicException::class);

        new SessionLottery(-1);
    }

    /**
     * @test
     */
    public function test_hits_can_fail(): void
    {
        for ($i = 0; $i < 50; ++$i) {
            $lottery = new SessionLottery(0);
            if ($lottery->wins()) {
                $this->fail('0% lottery won');
            }
        }

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function test_hits_can_pass(): void
    {
        for ($i = 0; $i < 50; ++$i) {
            $lottery = new SessionLottery(100);
            if (! $lottery->wins()) {
                $this->fail('100% lottery failed');
            }
        }

        $this->assertTrue(true);
    }
}
