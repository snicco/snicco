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
            "Parameter [$param_name] for route [$route_name] must match [$pattern] to generate an URL. Given [$provided_value]."
        );
    }

    public static function becauseRequiredParameterIsMissing(
        string $required_segment,
        string $route_name
    ): InvalidArgumentException {
        return new self(
            "Required parameter [$required_segment] is missing for route [$route_name]."
        );
    }

}