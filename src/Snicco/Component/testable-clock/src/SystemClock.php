<?php

declare(strict_types=1);

namespace Snicco\Component\TestableClock;

use DateTimeImmutable;
use DateTimeZone;

final class SystemClock implements Clock
{
    private DateTimeZone $timezone;

    public function __construct(?DateTimeZone $timezone = null)
    {
        $this->timezone = $timezone ?: new DateTimeZone(date_default_timezone_get());
    }

    public function currentTimestamp(): int
    {
        return $this->currentTime()->getTimestamp();
    }

    public function currentTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }

    public static function fromUTC(): SystemClock
    {
        return new self(new DateTimeZone('UTC'));
    }
}
