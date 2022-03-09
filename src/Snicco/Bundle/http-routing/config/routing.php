<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Option\RoutingOption;

return [
    /*
     * This configuration value is used for generation URLs among other things.
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
     */
    RoutingOption::WP_ADMIN_PREFIX => '/wp-admin',

    /*
     * You can leave this value as is for default WordPress installs.
     * If you have a different (custom) login path you can set it here and the UrlGenerator will
     * generate correct urls to your custom login endpoint.
     */
    RoutingOption::WP_LOGIN_PATH => '/wp-login.php',

    /*
     * This option should be a list of valid (absolute) directories
     */
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
     * The API prefix will be used to configure your API routes correctly and to determine if the current request
     * goes to an API-endpoint.
     * This option must be set if you are using api-routing. Feel free to remove it otherwise.
     */
    RoutingOption::API_PREFIX => '',

    /*
     * The following three values should only be changed if you are developing on different ports locally.
     * In that case, you should load these values dynamically from an environment file.
     */
    RoutingOption::HTTP_PORT => 80,
    RoutingOption::HTTPS_PORT => 443,
    RoutingOption::USE_HTTPS => true,
];
