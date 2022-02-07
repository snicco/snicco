<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Exception;

use LogicException;

/**
 * @api
 */
final class CantRemoveListener extends LogicException
{

    /**
     * @param array{0:class-string, 1:string } $identifier
     */
    public static function thatIsMarkedAsUnremovable(array $identifier, string $event_name): self
    {
        return new self(
            sprintf(
                'The listener [%s] is marked as unremovable for the event [%s].',
                implode(',', $identifier),
                $event_name
            )
        );
    }

}