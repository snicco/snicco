<?php

declare(strict_types=1);

namespace Snicco\SignedUrl\Contracts;

use DateTimeImmutable;

interface SignedUrlClock
{
    
    public function currentTime() :DateTimeImmutable;
    
    public function currentTimestamp() :int;
    
}