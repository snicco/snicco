<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Option;

final class MiddlewareOption
{
    /**
     * @var string
     */
    public const ALWAYS_RUN = 'always_run_middleware_groups';

    /**
     * @var string
     */
    public const GROUPS = 'middleware_groups';

    /**
     * @var string
     */
    public const ALIASES = 'middleware_aliases';

    /**
     * @var string
     */
    public const PRIORITY_LIST = 'middleware_priority';

    /**
     * @var string
     */
    public const KERNEL_MIDDLEWARE = 'kernel_middleware';
}
