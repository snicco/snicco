# Snicco - SessionBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/SessionBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle integrates [`snicco/session`](https://github.com/snicco/session) in applications based on [`snicco/kernel`](https://github.com/snicco/kernel).

The [`snicco/http-routing-bundle`](https://github.com/snicco/http-routing-bundle) is required for this bundle.

## Installation

```shell
composer install snicco/session-bundle
```

## Configuration

See [config/session.php](config/session.php) for the available configuration options.

If this file does not exist in your configuration directory the default configuration will be copied
the first time the kernel is booted in dev mode.

Add the [`SessionBundle`](src/SessionBundle.php) to your `bundles.php`
config file.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\Session\SessionBundle;

return [
    
    'bundles' => [
        Snicco\Component\Kernel\ValueObject\Environment::ALL => [
           SessionBundle::class
        ]   
    ]   
];
```

## Usage

This bundle contains several [middleware](src/Middleware) that manage the session lifecycle.

- [`AllowMutableSessionForReadVerbs`](src/Middleware/AllowMutableSessionForReadVerbs.php),allows starting writable session for GET requests.
- [`StatefulRequest`](src/Middleware/StatefulRequest.php), starts and saves a session.
- [`SessionNoCache`](src/Middleware/SessionNoCache.php), marks the response as not cacheable.
- [`ShareSessionWithViews`](src/Middleware/ShareSessionWithViews.php), adds an `ImmutableSession` and an instance of [`SessionErrors`](src/ValueObject/SessionErrors.php) to all `ViewResponses`.
- [`SaveResponseAttributes`](src/Middleware/SaveResponseAttributes.php), saves flash messages, errors and old-input in the session.

Its recommended configure the session middleware in the `middleware` config of the [`HttpRoutingBundle`](https://github.com/snicco/http-routing-bundle) like so:

```php
//path/to/config/middleware.php
use Snicco\Bundle\HttpRouting\Option\MiddlewareOption;
use Snicco\Bundle\Session\Middleware\AllowMutableSessionForReadVerbs;
use Snicco\Bundle\Session\Middleware\SaveResponseAttributes;
use Snicco\Bundle\Session\Middleware\SessionNoCache;
use Snicco\Bundle\Session\Middleware\ShareSessionWithViews;
use Snicco\Bundle\Session\Middleware\StatefulRequest;

return [

    MiddlewareOption::GROUPS => [
        'stateful' => [
            StatefulRequest::class,
            ShareSessionWithViews::class,
            SaveResponseAttributes::class,
//            SessionNoCache::class, optional
        ]   
    ],
    MiddlewareOption::ALIASES => [
        'session-allow-write' => AllowMutableSessionForReadVerbs::class,
        'session-no-cache' => SessionNoCache::class,
    ]
];
```

The session can be accessed on the **PSR-7** request:

```php
// inside a controller or middleware
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\MutableSession;

$request->getAttribute(ImmutableSession::class);

// Only for unsafe request methods or if allowed explicitly for read requests.
$request->getAttribute(MutableSession::class);
```

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
