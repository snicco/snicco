# PSR-16 session driver for [`snicco/session`](https://github.com/sniccowp/session)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/SessionPsr16Bridge/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This package provides a [`Psr16SessionDriver`](src/Psr16SessionDriver.php) for
the [`snicco/session`](https://github.com/sniccowp/session) library.

## Installation

```shell
composer require snicco/session-psr16-bridge
```

## Usage

```php
use Snicco\Bridge\SessionPsr16\Psr16SessionDriver;

$idle_timeout_in_seconds = /* Same value as in your session config here */
$psr16_cache = /* */

$psr16_session_driver = new Psr16SessionDriver($psr16_cache, $idle_timeout_in_seconds);
```


## Contributing

This repository is a read-only split of the development repo of the
[**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
