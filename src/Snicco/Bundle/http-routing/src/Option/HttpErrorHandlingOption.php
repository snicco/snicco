<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Option;

final class HttpErrorHandlingOption
{
    /**
     * @var string
     */
    public const DISPLAYERS = 'exception_displayers';

    /**
     * @var string
     */
    public const TRANSFORMERS = 'exception_transformers';

    /**
     * @var string
     */
    public const REQUEST_LOG_CONTEXT = 'exception_request_context';

    /**
     * @var string
     */
    public const LOG_LEVELS = 'exception_log_levels';

    /**
     * @var string
     */
    public const LOG_PREFIX = 'error_log_name';

    /**
     * @var string
     */
    public const KEY = 'http_error_handling';

    /**
     * @interal
     */
    public static function key(string $constant): string
    {
        return 'http_error_handling.' . $constant;
    }
}
