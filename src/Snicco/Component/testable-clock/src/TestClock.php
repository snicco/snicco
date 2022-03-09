<?php

declare(strict_types=1);

namespace Snicco\Component\TestableClock;

use DateInterval;
use DateTimeImmutable;

use function sprintf;

final class TestClock implements Clock
{
    private DateTimeImmutable $now;

    public function __construct(DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new DateTimeImmutable('now');
    }

    public function travelIntoFuture(int $seconds): void
    {
        $interval = sprintf('PT%sS', $seconds);
        $this->now = $this->now->add(new DateInterval($interval));
    }

    public function travelIntoPast(int $seconds): void
    {
        $interval = sprintf('PT%sS', $seconds);
        $this->now = $this->now->sub(new DateInterval($interval));
    }

    public function currentTimestamp(): int
    {
        return $this->now->getTimestamp();
    }

    public function currentTime(): DateTimeImmutable
    {
        return $this->now;
    }
}
