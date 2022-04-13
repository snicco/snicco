# Snicco - TemplatingBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/TemplatingBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle integrates [`snicco/templating`](https://github.com/snicco/session) in applications based on [`snicco/kernel`](https://github.com/snicco/kernel).

## Installation

```shell
composer install snicco/templating-bundle
```

## Configuration

See [config/templating.php](config/templating.php) for the available configuration options.

If this file does not exist in your configuration directory the default configuration will be copied
the first time the kernel is booted in dev mode.

Add the [`TemplatingBundle`](src/TemplatingBundle.php) to your `bundles.php`
config file.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\Templating\TemplatingBundle;

return [
    
    'bundles' => [
        Snicco\Component\Kernel\ValueObject\Environment::ALL => [
           TemplatingBundle::class
        ]   
    ]   
];
```

## Usage

### Templating Middleware

The [`TemplatingBundle`](src/TemplatingBundle.php) provides a [`TemplatingMiddleware`](src/TemplatingMiddleware.php) that
renders `ViewResponses` by using the templating engine of the [`snicco/templating`](https://github.com/snicco/templating) library.

It should replace the simpler `SimpleTemplating` middleware of the `http-routing-bundle`. 

### View context

When resolving the `TemplateEngine` from the booted kernel the following context will be available in all views:

- `url` => an instance of `UrlGenerator`
- `view` => the `TemplatEngine` instance itself

### Error handling

The [`TemplatingBundle`](src/TemplatingBundle.php) will register a [`TemplatingExceptionDisplayer`](src/TemplatingExceptionDisplayer.php) if the `http-routing-bundle` is used. 

This exception displayer can be added in your `http_error_handling` configuration.

It will display exceptions based on the HTTP status code.

An exception with status code `403` will be rendered with this displayer, if either the `path-to-templates/errors/403.php` or `path-to-templates/exceptions/403.php`
template file exists.

It is possible to create dedicated exception templates for exceptions thrown inside the **WordPress** admin area.
Just add the `-admin` suffix to the corresponding file name like so:

`path-to-templates/exceptions/403-admin.php`



## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
