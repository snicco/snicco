<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * @api
 */
final class ContainerIsLocked extends RuntimeException implements ContainerExceptionInterface
{

    public static function whileSettingId(string $id): ContainerIsLocked
    {
        return new self(
            "The id [$id] can not be set on the container because its locked already."
        );
    }

    public static function whileRemovingId(string $offset): ContainerIsLocked
    {
        return new self(
            "The id [$offset] can not be unset on the container because its locked already."
        );
    }

}