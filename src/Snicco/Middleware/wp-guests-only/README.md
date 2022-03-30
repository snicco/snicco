# Middleware for [`snicco/http-routing`](https://github.com/sniccowp/http-routing) to only allow access to guest **WordPress** users.

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/WPGuestsOnly/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)


## Installation

```shell
composer require snicco/wp-guest-only-middleware
```

## Usage

This middleware should be added on a per-route basis.

If a logged a user is logged in this middleware will try to redirect to a route named `dashbaord` if it exists.
Otherwise, the user is redirect to the homepage.

Optionally, a custom redirect path can be set by passing middleware arguments.

````php
// Assumes you have configured an alias for "guests-only" => WPGuestsOnly::class
// A logged-in user will now be redirected to /foo.
$configurator->get('route1', '/route1', SomeController::class)
             ->middleware('guests-only:/foo');
````

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
