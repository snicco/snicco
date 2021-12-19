<?php

declare(strict_types=1);

namespace Snicco\SignedUrl;

use DateTimeImmutable;
use Snicco\SignedUrl\Contracts\SignedUrlClock;

/**
 * @interal
 */
final class SignedUrlClockUsingDateTimeImmutable implements SignedUrlClock
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