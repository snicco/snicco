<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Option;

final class MiddlewareOption
{
    public const ALWAYS_RUN = 'always_run_middleware_groups';

    public const GROUPS = 'middleware_groups';

    public const ALIASES = 'middleware_aliases';

    public const PRIORITY_LIST = 'middleware_priority';

    public const KERNEL_MIDDLEWARE = 'kernel_middleware';

    /**
     * @interal
     */
    public static function key(string $constant): string
    {
        return 'middleware.' . $constant;
    }
}
