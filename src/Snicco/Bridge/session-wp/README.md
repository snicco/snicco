# WordPress session drivers for [`snicco/session`](https://github.com/snicco/session)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/SessionWPBridge/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This package provides two **WordPress** specific session drivers for
the [`snicco/session`](https://github.com/snicco/session) library:

- [`WPDBSessionDriver`](src/WPDBSessionDriver.php), uses the `wpdb` class to storage sessions in a custom table
- [`WPObjectCacheDriver`](src/WPObjectCacheDriver.php), uses the (persistent) `WP_Object_Cache` to store sessions.

## Installation

```shell
composer require snicco/session-wp-bridge
```

## Usage

### `WPDBSessionDriver`

To use the [`WPDBSessionDriver`](src/WPDBSessionDriver.php) you first need an instance
of [`BetterWPDB`](https://github.com/snicco/better-wpdb).

```php
use Snicco\Bridge\SessionWP\WPDBSessionDriver;
use Snicco\Component\BetterWPDB\BetterWPDB;

$driver = new WPDBSessionDriver('my_app_session', BetterWPDB::fromWpdb());

// Creates the necessary db table if it does not exist already.
$driver->createTable();
```

### `WPObjectCacheDriver`

```php
use Snicco\Bridge\SessionWP\WPObjectCacheDriver;

$idle_timeout_in_seconds /* Same value as in your session config here */

$wp_object_cache_driver = new WPObjectCacheDriver('my_app_sessions');
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
