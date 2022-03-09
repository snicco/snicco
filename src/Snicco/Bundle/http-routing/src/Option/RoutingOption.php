<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Option;

final class RoutingOption
{
    public const HOST = 'host';
    public const WP_ADMIN_PREFIX = 'wp_admin_prefix';
    public const WP_LOGIN_PATH = 'wp_login_path';
    public const ROUTE_DIRECTORIES = 'route_directories';
    public const API_ROUTE_DIRECTORIES = 'api_route_directories';
    public const API_PREFIX = 'api_prefix';
    public const HTTP_PORT = 'http_port';
    public const HTTPS_PORT = 'https_port';
    public const USE_HTTPS = 'https';

    /**
     * @interal
     */
    public static function key(string $constant): string
    {
        return 'routing.' . $constant;
    }
}
