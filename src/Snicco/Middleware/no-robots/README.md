# Middleware for [`snicco/http-routing`](https://github.com/snicco/http-routing) to disallow search engines

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/NoRobots/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This middleware for the [`snicco/http-routing`](https://github.com/snicco/http-routing) component allows you
to discourage search-engines from indexing the current request path by using the `X-Robots-Tag` header.

## Installation

```shell
composer require snicco/no-robots-middleware
```

## Usage

This middleware can be added globally, in a group or on a per-route basis. Choose what works
best for you.

```php
use Snicco\Middleware\NoRobots\NoRobots;

// Disallows robots entirely (noindex,no archive,nofollow)
$configurator->get('route1', '/route1', SomeController::class)
              ->middleware(NoRobots::class);

// noindex, no archive, nofollow header is not added because its set to false.
$configurator->get('route1', '/route1', SomeController::class)
              ->middleware(NoRobots::class. ':true,false,true');

```

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
