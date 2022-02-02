<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Exception;

use Closure;
use LogicException;

/**
 * @api
 */
final class CantRemove extends LogicException
{

    /**
     * @param Closure|array{0:class-string, 1:string } $listener
     */
    public static function listenerThatIsMarkedAsUnremovable($listener, string $event_name): self
    {
        $identifier = $listener instanceof Closure
            ? ['Closure', spl_object_hash($listener)]
            : $listener;

        return new self(
            sprintf(
                'The listener [%s] is marked as unremovable for the event [%s].',
                implode(',', $identifier),
                $event_name
            )
        );
    }

}