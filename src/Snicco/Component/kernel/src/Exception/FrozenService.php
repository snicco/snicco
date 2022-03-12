<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

final class FrozenService extends RuntimeException implements ContainerExceptionInterface
{
    public static function forId(string $id): FrozenService
    {
        return new self(sprintf('The id [%s] is locked because it is shared and has already been resolved.', $id));
    }
}
