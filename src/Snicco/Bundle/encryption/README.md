# Snicco - EncryptionBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/EncryptionBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This **WordPress** bundle integrates [`defuse/php-encryption`](https://github.com/defuse/php-encryption) in applications based on [`snicco/kernel`](https://github.com/snicco/kernel).

Make sure you have a read the [documentation of `defuse/php-encryption`](https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md).

## Installation

```shell
composer require snicco/encryption-bundle
```

## Configuration

See [config/encryption.php](config/encryption.php) for the available configuration options.

If this file does not exist in your configuration directory the default configuration will be copied
the first time the kernel is booted in dev mode.

The `EncryptionOption::KEY_ASCII` is intentionally set to a value that will throw an exception.

**MAKE SURE TO READ THE DOCUMENTATION IN THE [config/encryption.php](config/encryption.php) FILE**.

## Usage

You must first generate a valid defuse key by running:

```shell
vendor/bin/vendor/bin/generate-defuse-key
```

**MAKE SURE TO READ THE DOCUMENTATION IN THE [config/encryption.php](config/encryption.php) FILE** to determine the best
way to load the output of the above command into your configuration.

Add the [`EncryptionBundle`](src/EncryptionBundle.php) to your `bundles.php`
config file.

```php
<?php
// /path/to/configuration/bundles.php

use Snicco\Bundle\Encryption\EncryptionBundle;

return [

    'bundles' => [
        Snicco\Component\Kernel\ValueObject\Environment::ALL => [
           EncryptionBundle::class
        ]
    ]
];

```
You can now **lazily** resolve the [`DefuseEncryptor`](src/DefuseEncryptor.php) from the booted kernel.

```php
use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Component\Kernel\Kernel;

/**
* @var Kernel $kernel
*/
$kernel->boot();

$defuse = $kernel->container()->make(DefuseEncryptor::class);

$plaintext = 'snicco.io';

$ciphertext = $defuse->encrypt($plaintext);

var_dump($plaintext === $ciphertext); // false

$plain2 = $defuse->decrypt($ciphertext);

var_dump($plaintext === $plain2); // true
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
