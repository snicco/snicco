<?php

declare(strict_types=1);

namespace Tests\Session;

use DateTime;
use DateTimeInterface;
use DateTimeImmutable;
use Snicco\Session\Contracts\SessionClock;

class TestClock implements SessionClock
{
    
    /**
     * @var DateTimeImmutable
     */
    private $date_time;
    
    public function __construct(DateTimeInterface $date_time)
    {
        if ($date_time instanceof DateTime) {
            $date_time = DateTimeImmutable::createFromMutable($date_time);
        }
        $this->date_time = $date_time;
    }
    
    public function setCurrentTime(DateTimeInterface $date_time)
    {
        if ($date_time instanceof DateTime) {
            $date_time = DateTimeImmutable::createFromMutable($date_time);
        }
        $this->date_time = $date_time;
    }
    
    public function currentTime() :DateTimeImmutable
    {
        return $this->date_time;
    }
    
    public function currentTimestamp() :int
    {
        return $this->date_time->getTimestamp();
    }
    
}