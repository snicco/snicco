# Snicco - DebugBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/DebugBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle can be used together with the [`snicco/http-routing-bundle`](https://github.com/snicco/http-routing-bundle) in applications based on [`snicco/kernel`](https://github.com/snicco/kernel).

The DebugBundle configures [`Whoops`](https://github.com/filp/whoops) as the error handler for the middleware pipeline of your application.

**This bundle should never be used in production.**

## Installation

```shell
composer install snicco/debug-bundle
```

## Configuration

See [config/debug.php](config/debug.php) for the available configuration options.

If this file does not exist in your configuration directory the default configuration will be copied
the first time the kernel is booted in dev mode.

## Usage

Add the [`DebugBundle`](src/DebugBundle.php) to your `bundles.php`
config file **in the DEV environment**.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\Debug\DebugBundle;

return [
    
    'bundles' => [
        Snicco\Component\Kernel\ValueObject\Environment::DEV => [
           DebugBundle::class
        ]   
    ]   
];

```

If the kernel environment is `Environment::dev()` the whoops error handler will automatically be used.

To disable the whoops error handler set the kernel env to `Environment::dev(false)` (Passing false as a second argument means "no debug").

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
