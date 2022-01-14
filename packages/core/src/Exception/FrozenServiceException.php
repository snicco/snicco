<?php

declare(strict_types=1);

namespace Snicco\Core\Exception;

use Throwable;
use RuntimeException;
use Psr\Container\ContainerExceptionInterface;

final class FrozenServiceException extends RuntimeException implements ContainerExceptionInterface
{
    
    public static function from(Throwable $throwable) :FrozenServiceException
    {
        return new static($throwable->getMessage(), $throwable->getCode(), $throwable);
    }
    
}