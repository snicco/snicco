<?php

declare(strict_types=1);

namespace Snicco\Component\TestableClock;

use DateTimeImmutable;

final class SystemClock implements Clock
{
    
    public function currentTimestamp() :int
    {
        return $this->currentTime()->getTimestamp();
    }
    
    public function currentTime() :DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
    
}