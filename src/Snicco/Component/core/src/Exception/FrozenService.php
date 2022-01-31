<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Exception;

use RuntimeException;
use Psr\Container\ContainerExceptionInterface;

/**
 * @api
 */
final class FrozenService extends RuntimeException implements ContainerExceptionInterface
{
    
    public static function forId(string $id) :FrozenService
    {
        return new self(
            "The id [$id] is locked because it is shared and has already been resolved."
        );
    }
    
}