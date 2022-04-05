# Middleware for [`snicco/http-routing`](https://github.com/sniccowp/http-routing) that adds default headers to all responses.

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/DefaultHeaders/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

A middleware for the [`snicco/http-routing`](https://github.com/sniccowp/http-routing) component will add default headers
to all outgoing responses.


## Installation

```shell
composer require snicco/must-match-route-middleware
```

## Usage

This middleware should be added for specific groups or globally in the `MiddlewareResolver`. 

Choose what works best for you.

This middleware must be bound in the **PSR-11** container that is used by the [`snicco/http-routing`](https://github.com/sniccowp/http-routing) component.

```php
// In your container definitions
use Snicco\Middleware\DefaultHeaders\DefaultHeaders;

$default_headers = new DefaultHeaders([
    'X-Content-Type-Options' => 'nosniff' // key value pairs or header names and values.
])

```

## Contributing

This repository is a read-only split of the development repo of the
[**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
