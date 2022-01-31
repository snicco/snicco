<?php

declare(strict_types=1);

namespace Snicco\Component\TestableClock;

use DateTimeImmutable;

interface Clock
{

    public function currentTimestamp(): int;

    public function currentTime(): DateTimeImmutable;

}