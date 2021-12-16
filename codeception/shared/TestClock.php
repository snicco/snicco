<?php

declare(strict_types=1);

namespace Tests\Codeception\shared;

use DateTimeImmutable;
use Snicco\SignedUrl\Contracts\SignedUrlClock;

final class TestClock implements SignedUrlClock
{
    
    /**
     * @var int
     */
    private $timestamp;
    
    public function __construct()
    {
        $this->timestamp = time();
    }
    
    public function travelIntoFuture(int $seconds)
    {
        $this->timestamp = $this->timestamp + $seconds;
    }
    
    public function currentTime() :DateTimeImmutable
    {
        return (new DateTimeImmutable())->setTimestamp($this->timestamp);
    }
    
    public function currentTimestamp() :int
    {
        return $this->timestamp;
    }
    
}