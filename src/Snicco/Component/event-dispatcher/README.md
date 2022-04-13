# A PSR11/PSR-14-compatible EventDispatcher with zero dependencies

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/EventDispatcher/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

The **EventDispatcher** component of the [**Snicco** project](https://github.com/snicco/snicco) provides tools that allow your application components to communicate with each other by dispatching events and listening to them.

## Installation

```shell
composer require snicco/event-dispatcher
```

## Documentation

The **EventDispatcher** component is a completely standalone package with zero external dependencies.

The documentation can be found in the README of the [BetterWPHooks](https://github.com/snicco/better-wp-hooks), which is a small adapter around this package.

If you want to use this package in a non **WordPress** project simply skip the **WordPress** specific parts
of the [documentation](https://github.com/snicco/better-wp-hooks).

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
