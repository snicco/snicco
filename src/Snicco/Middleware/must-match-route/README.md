# Middleware for [`snicco/http-routing`](https://github.com/snicco/http-routing) that ensures that a route matched

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/MustMatchRoute/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

A middleware for the [`snicco/http-routing`](https://github.com/snicco/http-routing) component that will throw a
`404 HTTPException` if the returned response is an instance of `DelegatedResponse`.

## Installation

```shell
composer require snicco/must-match-route-middleware
```

## Usage

This middleware should be added for specific groups in the `MiddlewareResolver`.

There are no configuration options.

```php
$configurator->middleware(Snicco\Middleware\MustMatchRoute\MustMatchRoute::class)->group(function () {
   // 
});
```

## Contributing

This repository is a read-only split of the development repo of the
[**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
