# Snicco - BetterWPMailBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/BetterWPMailBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle configures the standalone [`snicco/better-wp-mail`](https://github.com/sniccowp/better-wp-mail) library for usage in applications based on [`snicco/kernel`](https://github.com/sniccowp/better-wp-cache).

## Installation

```shell
composer install snicco/better-wp-mail-bundle
```

## Configuration

See [config/mail.php](config/mail.php) for the available configuration options.

If this file does not exist in your configuration directory the default configuration will be copied
the first time the kernel is booted in dev mode.

## Usage

Add the [`BetterWPMailBundle`](src/BetterWPMailBundle.php) to your `bundles.php` configuration file.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\BetterWPMail\BetterWPMailBundle;

return [
    
    'bundles' => [
        Snicco\Component\Kernel\ValueObject\Environment::ALL => [
            BetterWPMailBundle::class
        ]   
    ]   
];

```

You can now **lazily** resolve a [`Mailer`](https://github.com/sniccowp/better-wp-mail) from the booted kernel.

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\EventDispatcher\EventDispatcher;

/**
* @var Kernel $kernel
*/
$kernel->boot();

$mailer = $kernel->container()->make(Mailer::class);
```

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
