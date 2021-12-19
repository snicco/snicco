<?php

declare(strict_types=1);

namespace Snicco\Core\ExceptionHandling\Exceptions;

use RuntimeException;

final class BadRouteParameter extends RuntimeException
{
    
    public static function becauseRegexDoesntMatch(string $provided_value, string $param_name, string $pattern, string $route_name) :BadRouteParameter
    {
        return new self(
            "Parameter [$param_name] for route [$route_name] must match [$pattern] to generate an URL. Given [$provided_value]."
        );
    }
    
    public static function becauseRequiredParameterIsMissing(string $required_segment, string $route_name) :RuntimeException
    {
        return new RuntimeException(
            "Required parameter [$required_segment] is missing for route [$route_name]."
        );
    }
    
}