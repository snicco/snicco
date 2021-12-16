# Adds WordPress specific storage for the [sniccowp/signed-url](https://github.com/sniccowp/sniccowp/tree/feature/extract-magic-link/packages/signed-url) library

Make sure read the
general [documenation](https://github.com/sniccowp/sniccowp/tree/feature/extract-magic-link/packages/signed-url) to know
how to familiarize yourself with the general concepts.

## Installation

```shell
composer require sniccowp/signed-url-wp
```

### Storage types

#### wpdb:

Uses the WordPress wpdb connection that WordPress creates for every request.

```php
$table_name = 'wp_magic_links';
$storage = new \Snicco\SignedUrlWP\Storage\WPDBStorage($table_name)
```

You have to set up a database table with the provided the following schema:

``` sql
'CREATE TABLE `signed_urls` (
`id` varchar(255) NOT NULL,
`expires` int(11) unsigned NOT NULL,
`left_usages` tinyint unsigned NOT NULL,
`protects` varchar(255) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `link_expires_at_index` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; '
```

### wp object cache:

Uses the wp_object cache functions (only use it if you have a persistent object cache).

```php
$cache_group = 'my_group_prefix';
$storage = new \Snicco\SignedUrlWP\Storage\WPObjectCacheStorage($cache_group)
```