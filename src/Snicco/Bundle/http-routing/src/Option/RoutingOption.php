<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Option;

final class RoutingOption
{
    /**
     * @var string
     */
    public const HOST = 'host';

    /**
     * @var string
     */
    public const WP_ADMIN_PREFIX = 'wp_admin_prefix';

    /**
     * @var string
     */
    public const WP_LOGIN_PATH = 'wp_login_path';

    /**
     * @var string
     */
    public const ROUTE_DIRECTORIES = 'route_directories';

    /**
     * @var string
     */
    public const API_ROUTE_DIRECTORIES = 'api_route_directories';

    /**
     * @var string
     */
    public const API_PREFIX = 'api_prefix';

    /**
     * @var string
     */
    public const HTTP_PORT = 'http_port';

    /**
     * @var string
     */
    public const HTTPS_PORT = 'https_port';

    /**
     * @var string
     */
    public const USE_HTTPS = 'https';
}
