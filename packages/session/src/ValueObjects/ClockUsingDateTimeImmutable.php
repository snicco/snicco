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
    
    /**
     * @var DateTimeImmutable
     */
    private $time;
    
    public function __construct()
    {
        $this->time = new DateTimeImmutable('now');
    }
    
    public function currentTime() :DateTimeImmutable
    {
        return $this->time;
    }
    
    public function currentTimestamp() :int
    {
        return $this->time->getTimestamp();
    }
    
}