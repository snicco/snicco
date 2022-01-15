<?php

declare(strict_types=1);

namespace Snicco\SignedUrl\Exceptions;

use Throwable;
use InvalidArgumentException;

/**
 * @api
 */
final class BadIdentifier extends InvalidArgumentException
{
    
    public static function for(string $id, Throwable $previous = null) :BadIdentifier
    {
        return new self(
            "The identifier [$id] does not exists.",
            $previous ? $previous->getCode() : 0,
            $previous
        );
    }
    
}