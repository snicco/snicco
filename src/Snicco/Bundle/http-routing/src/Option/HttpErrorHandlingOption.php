<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Option;

final class HttpErrorHandlingOption
{
    public const DISPLAYERS = 'exception_displayers';
    public const TRANSFORMERS = 'exception_transformers';
    public const REQUEST_LOG_CONTEXT = 'exception_request_context';
    public const LOG_LEVELS = 'exception_log_levels';
    public const LOG_PREFIX = 'error_log_name';

    public const KEY = 'http_error_handling';

    /**
     * @interal
     */
    public static function key(string $constant): string
    {
        return 'http_error_handling.' . $constant;
    }

}