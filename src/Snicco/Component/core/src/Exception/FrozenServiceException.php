<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Exception;

use Throwable;
use RuntimeException;
use Psr\Container\ContainerExceptionInterface;

/**
 * @api
 */
final class FrozenServiceException extends RuntimeException implements ContainerExceptionInterface
{
    
    public static function fromPrevious(Throwable $throwable) :FrozenServiceException
    {
        return new self($throwable->getMessage(), $throwable->getCode(), $throwable);
    }
    
}