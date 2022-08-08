# Testing utils for [`snicco/better-wp-cli`](https://github.com/snicco/better-wp-cli)

This package provides a `CommandTester` class that allows testing CLI-commands
created with [`snicco/better-wp-cli`](https://github.com/snicco/better-wp-cli) without having
to run the entire `wp` runner from end-to-end.

## Installation

```shell
composer require snicco/better-wp-cli-testing
```

## Usage

```php
use Snicco\Component\BetterWPCLI\Testing\CommandTester;

$tester = new CommandTester(new CreateUserCommand());

$tester->run(['calvin', 'calvin@snicco.io'], ['send-email' => true]);

$tester->assertCommandIsSuccessful();

$tester->assertStatusCode(0);

$tester->seeInStdout('User created!');

$tester->dontSeeInStderr('Fail');
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
