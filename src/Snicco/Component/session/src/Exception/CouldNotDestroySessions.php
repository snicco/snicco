<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use RuntimeException;
use Throwable;

use function implode;
use function intval;
use function sprintf;

/**
 * @api
 */
final class CouldNotDestroySessions extends RuntimeException
{

    /**
     * @param string[] $ids
     */
    public static function forSessionIDs(
        array $ids,
        string $driver_identifier,
        Throwable $previous = null
    ): CouldNotDestroySessions {
        return new self(
            sprintf(
                'Cant destroy session ids [%s] with the [%s] driver.',
                implode(',', $ids),
                $driver_identifier
            ), intval($previous ? $previous->getCode() : 0), $previous
        );
    }

}