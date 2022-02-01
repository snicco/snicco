<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Exception;

use RuntimeException;
use Throwable;

use function implode;
use function sprintf;

/**
 * @api
 */
final class CantDestroySession extends RuntimeException
{

    public static function forId($id, string $driver_identifier, Throwable $previous = null): CantDestroySession
    {
        return new self(
            "Cant destroy session [$id] with the [$driver_identifier] driver.", 0, $previous
        );
    }

    public static function forSessionIDs(
        array $ids,
        string $driver_identifier,
        Throwable $previous = null
    ): CantDestroySession {
        return new self(
            sprintf(
                'Cant destroy session ids [%s] with the [%s] driver.',
                implode(',', $ids),
                $driver_identifier
            ), 0, $previous
        );
    }

}