<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

use function get_class;

final class GenericEvent implements Event
{
    private array $arguments;

    private string $name;

    public function __construct(string $name, array $arguments = [])
    {
        $this->arguments = $arguments;
        $this->name = $name;
    }

    public static function fromObject(object $event): GenericEvent
    {
        return new self(get_class($event), [$event]);
    }

    public function payload(): array
    {
        return $this->arguments;
    }

    public function name(): string
    {
        return $this->name;
    }
}
