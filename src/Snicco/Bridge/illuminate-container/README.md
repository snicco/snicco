# Provides an adapter for `illuminate/container` for [`snicco/kernel`](https://github.com/snicco/kernel)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/IlluminateContainerBridge/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

## Installation

```shell
composer require snicco/illuminate-container-bridge
```

## Usage

```php
use Illuminate\Container\Container;
use Snicco\Bridge\IlluminateContainer\IlluminateContainerAdapter;

$pimple_container_adapter = new IlluminateContainerAdapter(
    new Container(); // Your illuminate container
)
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
