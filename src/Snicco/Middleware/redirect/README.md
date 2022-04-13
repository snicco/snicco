# Middleware for [`snicco/http-routing`](https://github.com/snicco/http-routing) to configure bulk redirects.

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/Redirect/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This middleware for the [`snicco/http-routing`](https://github.com/snicco/http-routing) component allows
mass redirects based on configuration files.

## Installation

```shell
composer require snicco/redirect-middleware
```

## Usage

This middleware should be added **globally** in the `MiddlewareResolver`.

The [`Redirect`](src/Redirect.php) middleware must be bound in the **PSR-11** container that is used
by the [`snicco/http-routing`](https://github.com/snicco/http-routing) component.

```php
// In your configuration for your PSR-11 container
use Snicco\Middleware\Redirect\Redirect;

$redirects = [
    307 => [
      '/foo' => '/bar'  
    ],
    301 =>  [
      '/baz' => 'biz?boom=bang'
      '/boom' => 'https://external-page.com'
    ]   
];

// You can also load the redirects from a file.
$redirect = new Redirect($redirects);
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
