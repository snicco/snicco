<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\Tests\fixtures;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

final class FilterEvent implements Event, ExposeToWP
{
    use ClassAsName;
    use ClassAsPayload;

    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
