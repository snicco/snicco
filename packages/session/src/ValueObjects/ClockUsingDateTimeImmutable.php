<?php

declare(strict_types=1);

namespace Snicco\Session\ValueObjects;

use DateTimeImmutable;
use Snicco\Session\Contracts\SessionClock;

/**
 * @api
 */
final class ClockUsingDateTimeImmutable implements SessionClock
{
    
    public function currentTime() :DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
    
    public function currentTimestamp() :int
    {
        return time();
    }
    
}