# Snicco - BladeBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/BladeBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle configures the standalone [`snicco/blade-bridge`](https://github.com/sniccowp/blade-bridge) library for usage in applications based on [`snicco/kernel`](https://github.com/sniccowp/kernel).

## Installation

```shell
composer install snicco/blade-bundle
```

## Configuration

This bundle has no configuration options currently.

## Usage

Add the [`TemplatingBundle`](https://github.com/sniccowp/templating-bundle) and the [`BladeBundle`](src/BladeBundle.php) to your `bundles.php`
config file.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\Blade\BladeBundle;
use Snicco\Bundle\Templating\TemplatingBundle;

return [
    
    'bundles' => [
        Snicco\Component\Kernel\ValueObject\Environment::ALL => [
            TemplatingBundle::class,
            BladeBundle::class
        ]   
    ]   
];

```

You can now render `.blade.php` with the [`TemplateEngine`](https://github.com/sniccowp/templating#usage) that is bound in the kernel container.

```php
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Templating\TemplateEngine;

/**
* @var Kernel $kernel
*/
$kernel->boot();

$template_engine = $kernel->container()->make(TemplateEngine::class);

// Assuming you have a welcome.blade.php view

$template_engine->renderView('welcome', ['greet' => 'Calvin']);
```

The [`BladeBundle`](src/BladeBundle.php) reconfigures a couple of the [disabled blade directives](https://github.com/sniccowp/blade-bridge#blade-features):

- `@auth` can be used to check if the current **WordPress** user is **logged in**.
- `@guest` can be used to check if the current **WordPress** user is **logged out**.
- `@role` can be used to check a role of the current **WordPress** user, e.g. `@role(editor)`

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
