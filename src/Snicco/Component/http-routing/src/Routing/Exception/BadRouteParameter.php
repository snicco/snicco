<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Exception;

use InvalidArgumentException;

final class BadRouteParameter extends InvalidArgumentException
{
    public static function becauseRegexDoesntMatch(
        string $provided_value,
        string $param_name,
        string $pattern,
        string $route_name
    ): self {
        return new self(
            sprintf(
                'Parameter [%s] for route [%s] must match [%s] to generate an URL. Given [%s].',
                $param_name,
                $route_name,
                $pattern,
                $provided_value
            )
        );
    }

    public static function becauseRequiredParameterIsMissing(
        string $required_segment,
        string $route_name
    ): BadRouteParameter {
        return new self(sprintf('Required parameter [%s] is missing for route [%s].', $required_segment, $route_name));
    }
}
