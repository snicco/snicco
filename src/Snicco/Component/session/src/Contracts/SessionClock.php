<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

use DateTimeImmutable;

/**
 * @interal
 */
interface SessionClock
{
    
    public function currentTimestamp() :int;
    
    public function currentTime() :DateTimeImmutable;
    
}