<?php

declare(strict_types=1);

namespace Tests\Session\unit;

use LogicException;
use Tests\Codeception\shared\UnitTest;
use Snicco\Session\ValueObjects\SessionLottery;

final class SessionLotteryTest extends UnitTest
{
    
    /** @test */
    public function testExceptionHighPercentage()
    {
        $this->expectException(LogicException::class);
        
        $lottery = new SessionLottery(101);
    }
    
    /** @test */
    public function testExceptionLowPercentage()
    {
        $this->expectException(LogicException::class);
        
        $lottery = new SessionLottery(-1);
    }
    
    /** @test */
    public function testHitsCanFail()
    {
        for ($i = 0; $i < 50; $i++) {
            $lottery = new SessionLottery(0);
            if ($lottery->wins()) {
                $this->fail('0% lottery won');
            }
        }
        $this->assertTrue(true);
    }
    
    /** @test */
    public function testHitsCanPass()
    {
        for ($i = 0; $i < 50; $i++) {
            $lottery = new SessionLottery(100);
            if ( ! $lottery->wins()) {
                $this->fail("100% lottery failed");
            }
        }
        $this->assertTrue(true);
    }
    
}