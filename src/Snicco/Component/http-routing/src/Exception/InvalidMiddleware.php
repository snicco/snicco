<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Exception;

use InvalidArgumentException;

final class InvalidMiddleware extends InvalidArgumentException
{
    public static function becauseItsNotAnAliasOrGroup(string $alias): InvalidMiddleware
    {
        return new self(sprintf('The middleware [%s] is not an alias or group name.', $alias, ));
    }
}
