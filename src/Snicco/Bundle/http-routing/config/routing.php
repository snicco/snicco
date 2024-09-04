<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Option\RoutingOption;

return [
    /*
     * This configuration value is used for generating URLs, among other things.
     * There are 2 recommended options to set this value depending on your use case:
     * a) You are distributing code:
     *    - Parse this value from the site_url WordPress option
     * b) You are developing for a controlled environment:
     *    - Load this value from an environment variable using something like symfony/dotenv.
     */
    RoutingOption::HOST => '127.0.0.1',

    /*
     * You can leave this value as is for default WordPress installs.
     * If you are using Bedrock you would have to set this to /wp/wp-admin.
     *
     * You can also set this value to NULL, in which case
     * it will be determined at boot time based on the admin_url()
     * method.
     * However, note that the admin routes will still be in the route cache,
     * so if the admin path is expected to change, you'll need to clear the cache.
     */
    RoutingOption::WP_ADMIN_PREFIX => '/wp-admin',

    /*
     * You can leave this value as is for default WordPress installs.
     * If you have a different (custom) login path you can set it here and the UrlGenerator will
     * generate correct urls to your custom login endpoint.
     */
    RoutingOption::WP_LOGIN_PATH => '/wp-login.php',

    // This option should be a list of valid (absolute) directories
    RoutingOption::ROUTE_DIRECTORIES => [
        //        dirname(__DIR__) . '/routes'
    ],

    /*
     * This option should be a list of valid (absolute) directories.
     * Feel free to delete it if your project does not need the API routing functionality
     */
    RoutingOption::API_ROUTE_DIRECTORIES => [
        //                dirname(__DIR__).'/routes/api',
        //                dirname(__DIR__).'/api-routes'
    ],

    /*
     * The API_PREFIX is prepended to all routes inside the API_ROUTE_DIRECTORIES IF you are using
     * the DefaultRouteLoadingOptions class. This is the case by default.
     *
     * If you are not using api-routes, or if you have bound a custom RouteLoadingOptions class in the container
     * you can remove this option.
     */
    RoutingOption::API_PREFIX => '',
    //RoutingOption::API_PREFIX => '/my-plugin',

    /*
     * This option determines which request are considered API requests, which consequently
     * will be run much earlier in the WP loading cycle.
     *
     * If you are not using a custom RouteLoadingOption class you can remove this option as the
     * bundle will take care of adding the value of RoutingOption::API_PREFIX to this configuration
     * value automatically.
     */
    RoutingOption::EARLY_ROUTES_PREFIXES => [],
    //RoutingOption::EARLY_ROUTES_PREFIXES => ['/my-plugin/foo', '/my-plugin/bar'],

    /*
     * The following three values should only be changed if you are developing on different ports locally.
     * In that case, you should load these values dynamically from an environment file.
     */
    RoutingOption::HTTP_PORT => 80,
    RoutingOption::HTTPS_PORT => 443,
    RoutingOption::USE_HTTPS => true,
];
