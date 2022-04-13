# Content negotiation middleware for [`snicco/http-routing`](https://github.com/snicco/http-routing)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/Negotiation/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

A middleware that uses [`middlewares/negotiation`](https://github.com/middlewares/negotiation) with some sensible
defaults.

It will determine the best content-type and content-language based on the provided `Accept` headers and will update
the `Accept` header of the request accordingly so that it only contains one content-type/content-language.

If no matching content type can be determined a `406 HTTPException` is thrown.

## Installation

```shell
composer require snicco/content-negotiation-middleware
```

## Usage

This middleware should be added globally in the `MiddlewareResolver`.

It must be bound in the **PSR-11** container that the [`snicco/http-routing`](https://github.com/snicco/http-routing)
component uses.

```php
// In your container definitions
use Snicco\Middleware\Negotiation\NegotiateContent;

// basic configuration based on defaults.
$negotiation = new NegotiateContent(
   ['en'] // content languages
);

// With custom configuration for content-types your application can provide (sorted by priority)
$negotiation = new NegotiateContent(
   ['en'],
   [ 
       'html' => [
                'extension' => ['html', 'php'],
                'mime-type' => ['text/html'],
                'charset' => true,
            ],
       'txt' => [
                'extension' => ['txt'],
                'mime-type' => ['text/plain'],
                'charset' => true,
            ],
       'json' => [
                'extension' => ['json'],
                'mime-type' => ['application/json'],
                'charset' => true,
            ],
   ] 
);
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
