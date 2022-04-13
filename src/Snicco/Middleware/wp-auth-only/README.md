# WordPress authentication middleware for [`snicco/http-routing`](https://github.com/snicco/http-routing)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/WPGuestsOnly/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This middleware checks if a **WordPress** user is logged in
and will throw a `401 HTTPExcetion` if that's not the case.

## Installation

```shell
composer require snicco/wp-auth-only-middleware
```

## Usage

This middleware should be added on a per-route basis.

There are no configuration options.

````php
use Snicco\Middleware\WPCap\AuthorizeWPCap;

$configurator->get('route1', '/route1', SomeController::class)
             ->middleware(AuthenticateWPUser::class);
````

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
