<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Throwable;

use function sprintf;

final class CantCreateListener extends RuntimeException
{
    public static function becauseTheListenerWasNotInstantiatable(
        string $listener,
        string $event_name,
        Throwable $previous
    ): self {
        $message =
            sprintf(
                'The listener class [%s] could not be instantiated while dispatching [%s].',
                $listener,
                $event_name
            );

        return new self($message, (int) $previous->getCode(), $previous);
    }

    public static function fromPrevious(
        string $listener_class,
        string $event_name,
        ContainerExceptionInterface $e
    ): self {
        return new self(
            sprintf(
                "Cant create listener class [%s] for event [%s].\n[%s]",
                $listener_class,
                $event_name,
                $e->getMessage()
            ),
            (int) $e->getCode(),
            $e
        );
    }
}
