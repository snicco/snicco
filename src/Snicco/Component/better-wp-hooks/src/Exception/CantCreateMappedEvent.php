<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\Exception;

use RuntimeException;
use Throwable;

use function array_map;
use function gettype;
use function implode;
use function sprintf;

final class CantCreateMappedEvent extends RuntimeException
{
    public static function becauseTheEventCouldNotBeConstructorWithArgs(
        array $wordpress_hook_arguments,
        string $event_class,
        Throwable $previous
    ): self {
        $args = array_map(fn ($arg) => gettype($arg), $wordpress_hook_arguments);

        $message = "The mapped event [%s] could not be instantiated with the passed received arguments from WordPress.\nReceived [%s].";

        $message = sprintf($message, $event_class, implode(',', $args));

        return new CantCreateMappedEvent($message, (int)$previous->getCode(), $previous);
    }
}
