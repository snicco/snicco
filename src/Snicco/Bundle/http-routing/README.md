# Snicco - HttpRoutingBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/HttpRoutingBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle configures the standalone [`snicco/http-routing`](https://github.com/snicco/http-routing)
library for usage in applications based on [`snicco/kernel`](https://github.com/snicco/kernel).

## Installation

```shell
composer install snicco/http-routing-bundle
```

## Configuration

This bundle has extensive configuration options:

- See [config/routing.php](config/routing.php) for the available routing configuration options.
- See [config/middleware.php](config/middleware.php) for the available middleware configuration options.
- See [config/http_error_handling.php](config/http_error_handling.php) for the available error handling configuration
  options.

If these files do not exist in your configuration directory the default configuration will be copied the first time the
kernel is booted in dev mode.

Add the [`HttpRoutingBundle`](src/HttpRoutingBundle.php) to your `bundles.php` configuration file.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\HttpRouting\HttpRoutingBundle;

return [
    
    'bundles' => [
        Snicco\Component\Kernel\ValueObject\Environment::ALL => [
            HttpRoutingBundle::class
        ]   
    ]   
];
```

## Usage

This bundle provides to main services that should be used:

- [`HttpKernelRunner`](src/HttpKernelRunner.php): Sends the current request at the right time through your application.
  This class serves as an entrypoint to your HTTP request-response cycle.
- [`WPAdminMenu`](src/WPAdminMenu.php): Uses the `AdminMenu` that is configured by your route definitions to integration
  them with the **WordPress** admin menu.

This is an example of how the bootstrap file of a plugin could look like:

```php
<?php

use Snicco\Bundle\HttpRouting\HttpKernelRunner;
use Snicco\Bundle\HttpRouting\WPAdminMenu;
use Snicco\Component\Kernel\Kernel;

// Create kernel

/**
 * @var Kernel $kernel 
 */
$kernel->boot();

/**
 * @var HttpKernelRunner $runner
 */
$runner = $kernel->container()->make(HttpKernelRunner::class);

$is_admin = is_admin();

$runner->listen($is_admin);

if ($is_admin) {
    /**
     * @var WPAdminMenu $admin_menu
     */
    $admin_menu = $kernel->container()->make(WPAdminMenu::class);
    $admin_menu->setUp('my-plugin-text-domain');
}
```

### Requests and Responses

This bundle takes care of running the middleware pipeline of
the [`snicco/http-routing`](https://github.com/snicco/http-routing) library at just the right moments inside the
**WordPress** request lifecycle.

There are three types of requests that can be handled:

- **API requests**, requests where the URI path starts with the API path prefix defined in your [routing configuration](config/routing.php).
- **Admin requests**, request that go to the WordPress admin area.
- **Frontend requests**, all other requests.

The `HttpKernelRunner::listen()` method sets appropriate hooks based on the current request type and will then pipe a
request through your application.

All of your middlewares and controllers have three types of responses that can be returned:

1. `DelegatedResponse`, with headers that should be sent
2. `DelegatedResponse`, without headers
3. Any other **PSR-7** response

Depending on the current request type here is what will happen for each response type:

- **API-request / Frontend-request:**

| Response type                   | Action                                                                    |
|---------------------------------|---------------------------------------------------------------------------|
| Delegated Response              | Response headers will be sent, WordPress execution resumes                |
| Delegated Response (no headers) | Nothing will be sent, WordPress execution resumes                         |
| Other **PSR-7** responses       | Response headers and body will be sent, WordPress execution is terminated |

- **Admin-request:**

| Response type                   | Action                                                                                                                                                                                                                                                                       |
|---------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Delegated Response              | Response headers will be sent, WordPress execution resumes                                                                                                                                                                                                                   |
| Delegated Response (no headers) | Nothing will be sent, WordPress execution resumes                                                                                                                                                                                                                            |
| Other **PSR-7** responses       | HTTP status code between 200 and 300: <br/>=> Headers are sent immediately. <br/>=> Response body is sent at the `all_admin_notices` hook.<br/><br/>HTTP status code between 300 and 599:<br/> => Headers and Body sent immediately<br/>=> WordPress execution is terminated |

### Middleware

This bundle provides a couple [**PSR-15** middlewares](src/Middleware) that you can use in your application:

- [`ErrorsToExceptions`](src/Middleware/ErrorsToExceptions.php): Transforms all errors inside your middleware pipeline to proper exceptions. **Strongly recommended.**
- [`SetUserId`](src/Middleware/SetUserId.php): Adds the user id of the current **WordPress** user to the request.
- [`SimpleTemplating`](src/Middleware/SimpleTemplating.php): Adds a simple middleware to render `ViewResponses` where the view name has to be an absolute path.

### Error handling

This bundle automatically configures the [`snicco/psr7-error-handler`](https://github.com/snicco/psr7-error-handler) library that is used by [`snicco/http-routing`](https://github.com/snicco/http-routing).

You can configure this behaviour with the [http_error_handling](config/http_error_handling.php) configuration.

All exceptions inside **YOUR** middleware pipeline will be handled automatically. **WordPress** core code and plugins are not affected by this at all.

## Contributing

This repository is a read-only split of the development repo of the
[**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
