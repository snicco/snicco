# PSR-16 cache bridge for [`snicco/signed-url`](https://github.com/snicco/signed-url)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/SignedUrlPsr16Bridge/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This package provides a [`Psr16Storage`](src/Psr16Storage.php) for [`snicco/signed-url`](https://github.com/snicco/signed-url) that allows you to use any **PSR-16** cache as a storage backend for signed-urls.

## Installation

```shell
composer require snicco/signed-url-psr16-bridge
```

## Usage

```php
use Snicco\Bridge\SignedUrlPsr16\Psr16Storage;

$psr_16_cache = /* your instantiated psr-16 cache*/

$psr_16_storage = new Psr16Storage($psr_16_cache);
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
