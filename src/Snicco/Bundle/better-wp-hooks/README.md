# Snicco - BetterWPHooksBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/BetterWPHooksBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle configures the standalone [`snicco/better-wp-hooks`](https://github.com/snicco/better-wp-hooks) library for usage in applications based on [`snicco/kernel`](https://github.com/snicco/kernel).

## Installation

```shell
composer require snicco/better-wp-hooks-bundle
```

## Configuration

This bundle currently has no configuration options.

## Usage

Add the [`BetterWPHooksBundle`](src/BetterWPHooksBundle.php) to your `bundles.php` configuration file.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [

    'bundles' => [
        Environment::ALL => [
            BetterWPHooksBundle::class
        ]
    ]
];

```

You can now **lazily** resolve the following services after booting the kernel:

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;


/**
* @var Kernel $kernel
*/
$kernel->boot();

$event_dispatcher = $kernel->container()->make(EventDispatcher::class);

$psr_event_dispatcher = $kernel->container()->make(EventDispatcherInterface::class);

var_dump($event_dispatcher === $psr_event_dispatcher); // true

$event_mapper = $kernel->container()->make(EventMapper::class);
```

If the [`snicco/event-dispatcher-testing`](https://github.com/snicco/event-dispatcher-testing) package is installed
a [`TestableEventDispatcher`](https://github.com/snicco/event-dispatcher-testing) will automatically be used in the testing environment.

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
