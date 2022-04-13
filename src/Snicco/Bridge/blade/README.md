# A standalone version of Laravel's Blade template engine for [`snicco/templating`](https://github.com/snicco/templating)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/BladeBridge/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This package allows using the [blade template engine](https://laravel.com/docs/9.x/blade) without the fullstack laravel
framework.

100% tested with full support for all features of blade, including view composers.

This package must be used together with [`snicco/templating`](https://github.com/snicco/templating).

## Installation

```shell
composer require snicco/blade-bridge
```

## Usage

### Creating a `BladeViewFactory`

To start rendering `.blade.php` views with the template engine
of [`snicco/templating`](https://github.com/snicco/templating) we need to create a `BladeViewFactory` and pass it to
the template engine.

```php
use Snicco\Bridge\Blade\BladeStandalone;
use Snicco\Bridge\Blade\BladeViewFactory;
use Snicco\Component\Templating\Context\ViewContextResolver;
use Snicco\Component\Templating\TemplateEngine;

/**
* @var ViewContextResolver $view_context_resolver
*/
$view_context_resolver = /* Check the documentation of snicco/templating */

$blade = new BladeStandalone(
    __DIR__.'/cache/blade', // directory path for the compiled templates
    [
       __DIR__.'/views',
       __DIR__.'/templates',
    ], // An array of directories where views are located
    $view_context_resolver
);


$blade->boostrap();

/**
* @var BladeViewFactory
*/
$blade_view_factory = $blade->getBladeViewFactory();

$template_engine = new TemplateEngine(
    $blade_view_factory
);
```

You can now render any `.blade.php` views with the template engine.

### Blade features

All features of blade 8.x can be used. [Please consult the documentation](https://laravel.com/docs/8.x/blade).

There are some directives which are disabled by this package by default and will throw an exception when used because
they are not decoupled from Laravel's global helper functions.

You can always enable them again with your own implementation using `Blade::directive()`.

The following directives are disabled:

- auth
- guest
- method
- csrf
- service
- env
- production
- can
- cannot
- canany
- dd
- dump
- lang
- choice
- error
- inject

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
