<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\Tests\fixtures;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\Event;

final class CustomNameEvent implements ExposeToWP, Event
{
    public string $value;

    private ?string $name;

    public function __construct(string $value, string $name = null)
    {
        $this->value = $value;
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name ?: static::class;
    }

    public function payload()
    {
        return $this;
    }
}
