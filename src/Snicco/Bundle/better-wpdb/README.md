# Snicco - BetterWPDBBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/BetterWPDBBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle configures the standalone [`snicco/better-wpdb`](https://github.com/sniccowp/better-wpdb) library for usage in applications based on [`snicco/kernel`](https://github.com/sniccowp/kernel).

## Installation

```shell
composer install snicco/better-wpdb-bundle
```

## Configuration

This bundle has no configuration options currently.

## Usage

Add the [`BetterWPDBBundle`](src/BetterWPDBBundle.php) to your `bundles.php` configuration file.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;

return [
    
    'bundles' => [
        Snicco\Component\Kernel\ValueObject\Environment::ALL => [
            BetterWPDBBundle::class
        ]   
    ]   
];

```

You can now **lazily** resolve a [`BetterWPDB`](https://github.com/sniccowp/better-wpdb) instance from the booted kernel.

```php
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Kernel\Kernel;

/**
* @var Kernel $kernel
*/
$kernel->boot();

$better_wpdb = $kernel->container()->make(BetterWPDB::class);
```

If an instance of [`QueryLogger`](https://github.com/sniccowp/better-wpdb#logging) is bound in the kernel container is will be used when creating the `BetterWPDB` instance.
Otherwise, no queries will be logged.

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
