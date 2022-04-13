# BetterWPCache - A PSR-16/PSR-6 cache implementation using the WordPress object cache

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/BetterWPCache/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

**BetterWPCache** is a tiny library that allows you to use
the [persistent WordPress object cache](https://developer.wordpress.org/reference/classes/wp_object_cache/)
as a **PSR-16** cache or a **PSR-6** cache.

Additionally, **BetterWPCache** supports **cache tagging**.

This library is 100% integration tested against the
official [php-cache/integration-tests](https://github.com/php-cache/integration-tests) using
the [wp-redis cache](https://github.com/rhubarbgroup/redis-cache) by [Till Krüss](https://github.com/tillkruss).

## Table of contents

1. [Motivation](#motivation)
2. [Installation](#installation)
3. [Usage](#usage)
    1. [Does this work with any caching plugin?]()
    2. [PSR-6](#creating-a-psr-16-cache)
    3. [PSR-16](#creating-a-psr-6-cache)
    4. [Cache Tagging](#cache-tagging)
4. [Contributing](#contributing)
5. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
6. [Security](#security)

## Motivation

We developed this library because many **WordPress** related components of the
[**Snicco** project](https://github.com/snicco/snicco) require some form of caching.

To offer the greatest flexibility for users, they all only depend on
the [PSR cache interfaces](https://www.php-fig.org/psr/psr-6/).

But using these interfaces' meant that there was no way of using these components inside **WordPress**
with the already connected `WP_Object_Cache`.

**BetterWPCache** solves this need.

This library has three main use cases:

1. You are developing a **distributed WordPress library** and don't want to depend on the `WP_Object_Cache`.
2. You are using any PHP package that depends on the **PSR** cache interface, and you want to use your **WordPress**
   caching plugin for it.
3. You want **cache-tagging**, which is something that the `WP_Object_Cache` does not support.

Ideally, **WordPress** core would replace the custom `WP_Object_Cache` with the **PSR** cache interface instead, so that
the need for this library vanishes. In the meantime **BetterWPCache** will do the job.

## Installation

```shell
composer require snicco/better-wp-cache
```

## Usage

### Does this work with any caching plugin?

Short answer: **Yes!**

Long answer: As long as your caching plugin correctly implements the `wp_cache_xxx` functions. This library only uses
the
[official **WordPress** cache functions](https://github.com/WordPress/wordpress-develop/blob/5.9/src/wp-includes/cache.php).

---

### Creating a PSR-6 cache

This is how you create a **PSR-6** cache from your favorite **WordPress** cache plugin.

```php

use Snicco\Component\BetterWPCache\CacheFactory;

$cache_group = 'my_plugin';
$psr_6_cache = CacheFactory::psr6($cache_group);

```

---

### Creating a PSR-16 cache

This is how you create a **PSR-16** cache from your favorite **WordPress** cache plugin.

```php

use Snicco\Component\BetterWPCache\CacheFactory;

$cache_group = 'my_plugin';
$psr_16_cache = CacheFactory::psr16($cache_group);

```

---

### Cache tagging

For cache tagging a **PSR-6** cache is needed.

```php
use Snicco\Component\BetterWPCache\CacheFactory;

$cache_group = 'my_plugin';
$psr_6_cache = CacheFactory::psr6($cache_group);

$taggable_cache = CacheFactory::taggable($psr_6_cache);
```

`$taggable_cache` is an instance of [TaggableCacheItemPoolInterface](http://www.php-cache.com/en/latest/#tagging) which
is in the process of becoming a **PSR** standard.

Taken from the [official documentation](http://www.php-cache.com/en/latest/#tagging):

```php
use Snicco\Component\BetterWPCache\CacheFactory;

$cache = CacheFactory::taggable(CacheFactory::psr6('my_plugin'));

$item = $cache->getItem('tobias');
$item->set('value')->setTags(['tag0', 'tag1'])
$cache->save($item);

$item = $cache->getItem('aaron');
$item->set('value')->setTags(['tag0']);
$cache->save($item);

// Remove everything tagged with 'tag1'
$cache->invalidateTags(['tag1']);
$cache->getItem('tobias')->isHit(); // false
$cache->getItem('aaron')->isHit(); // true

$item = $cache->getItem('aaron');
echo $item->getPreviousTags(); // array('tag0')

// No tags will be saved again. This is the same as saving
// an item with no tags.
$cache->save($item);
```

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
