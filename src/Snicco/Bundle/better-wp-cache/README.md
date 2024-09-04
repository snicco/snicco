# Snicco - BetterWPCacheBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/BetterWPCacheBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle configures the standalone [`snicco/better-wp-cache`](https://github.com/snicco/better-wp-cache) library for usage in applications based on [`snicco/kernel`](https://github.com/snicco/kernel).

## Installation

```shell
composer require snicco/better-wp-cache-bundle
```

## Configuration

See [config/better-wp-cache.php](config/better-wp-cache.php) for the available configuration options.
If this file does not exist in your configuration directory the default configuration will be copied
the first time the kernel is booted in dev mode.

## Usage

Add the [`BetterWPCacheBundle`](src/BetterWPCacheBundle.php) to your `bundles.php` configuration file.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\BetterWPCache\BetterWPCacheBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [

    'bundles' => [
        Environment::ALL => [
            BetterWPCacheBundle::class
        ]
    ]
];

```

You can now **lazily** resolve the following services after booting the kernel:

```php
use Cache\TagInterop\TaggableCacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Snicco\Component\Kernel\Kernel;

/**
* @var Kernel $kernel
*/
$kernel->boot();

$psr6_cache = $kernel->container()->make(CacheItemPoolInterface::class);

$psr16_cache = $kernel->container()->make(CacheInterface::class);

$taggable_cache = $kernel->container()->make(TaggableCacheItemInterface::class);
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
