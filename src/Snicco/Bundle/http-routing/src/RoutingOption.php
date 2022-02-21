<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting;

/**
 * @codeCoverageIgnore
 */
final class RoutingOption
{
    public const HOST = 'host';

    public const WP_ADMIN_PREFIX = 'wp_admin_prefix';

    public const WP_LOGIN_PATH = 'wp_login_path';

    public const ROUTE_DIRECTORIES = 'route_directories';

    public const API_ROUTE_DIRECTORIES = 'api_route_directories';

    public const API_PREFIX = 'api_prefix';

    public const ALWAYS_RUN_MIDDLEWARE_GROUPS = 'always_run_middleware_groups';

    public const MIDDLEWARE_GROUPS = 'middleware_groups';

    public const MIDDLEWARE_ALIASES = 'middleware_aliases';

    public const MIDDLEWARE_PRIORITY = 'middleware_priority';

}