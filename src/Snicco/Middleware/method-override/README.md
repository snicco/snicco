# HTTP-method override middleware for [`snicco/http-routing`](https://github.com/snicco/http-routing)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/MethodOverride/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

A middleware for the [`snicco/http-routing`](https://github.com/snicco/http-routing) component that allows you
to override the HTTP method for `POST` requests to either `PATCH|PUT|DELELE` based on the post body.


## Installation

```shell
composer require snicco/method-override-middleware
```

## Usage

This middleware should be added for **globally** in the `MiddlewareResolver`.

To allow overwriting the HTTP method, either a `_method` must be present in the request body or the `X-HTTP-Method-Override`
header must be present.

Overwriting the HTTP method only works for `POST` methods. Valid values are `PATCH|PUT|DELELE`.

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
