# BetterWPCLI - Making the wp-cli even more awesome

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/BetterWPAPI/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)


## Table of contents

1. [Motivation](#motivation)
2. [Installation](#installation)
3. [Usage](#usage)
4. [Contributing](#contributing)
5. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
6. [Security](#security)

## Motivation

We developed this library for the **WordPress** related components of the
[**Snicco**](https://github.com/snicco/snicco) project.

Directly interacting with core functions was problematic for us because:

- Code becomes untestable without
    - a) booting the entire **WordPress** codebase or
    - b) using additional mocking frameworks ([mocking sucks](https://twitter.com/icooper/status/1036527957798412288)).
- The code becomes[ really hard to scope](https://github.com/humbug/php-scoper/issues/303) using the de-facto standard
  tool [PHPScoper](https://github.com/humbug/php-scoper).
- Static analysers like **Psalm** and **PHPStan** go crazy over core functions.

If you can't relate to these issues, you probably don't need this library.

**Edit:** The scoping problem was solved, by us creating
a [command-line-programm](https://github.com/snicco/php-scoper-excludes) that can be used to generate
a [list of all functions and classes](https://github.com/snicco/php-scoper-wordpress-excludes/blob/master/generated/exclude-wordpress-functions.json)
in the entire **WordPress** core codebase. For now, the best example on how to use it
is [the `scoper.inc.php` configuration](https://github.com/GoogleForCreators/web-stories-wp/blob/main/scoper.inc.php#L13)
of the [Google Web stories plugin](https://github.com/GoogleForCreators/web-stories-wp).

## Installation

```shell
composer require snicco/better-wp-api
```

## Usage

[This is the public API](https://github.com/snicco/better-wp-api/blob/master/src/BetterWPAPI.php#L36) of
**BetterWPAPI**. No public or protected methods will be added until a next major version.

The idea is to limit the interaction with **WordPress** core to this class.

This has worked very well for us in order to keep our code testable and scopable. You have a single class where you can
see all interaction that your library has with **core**. During tests, you can then simply swap this class with a test
double.

The important thing is that: **All classes that need anything from WordPress must accept this class as a constructor
dependency.**

A simple example: (for a real example, check out the 
[**BetterWPMail**](https://github.com/snicco/snicco/blob/licensing-and-docs/src/Snicco/Component/better-wp-mail/src/WPMailAPI.php)
component)

Assuming we have the following class:

```php
use Snicco\Component\BetterWPAPI\BetterWPAPI;

class CSVImporter {

    public function __construct(BetterWPAPI $wp = null) {
        $this->wp = $wp ?: new BetterWPAPI();
    }
    
    public function import(string $file){
    
        if(!$this->wp->currentUserCan('import-csv')) {
            throw new Exception('Not authorized');
        }
        // import csv.
    }
    
}
```

This is how we use it in production code.

```php
$importer = new CSVImporter();

$importer->import(__DIR__.'/orders.csv');
```

This is how we test the code. No bootstrapping **WordPress** needed.

```php 
class CSVImporterTest extends TestCase {

   /**
    * @test
    */
    public function that_missing_permissions_throw_an_exception() {
        
        $this->expectExceptionMessage('Not authorized');
        
        $wp = new class extends BetterWPAPI {
            public function currentUserCan():bool {
                return false;
            }
        }
        
        $importer = new CSVImporter($wp);
        $importer->import(__DIR__.'/test-users.csv');
    }

}

```

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPAPI**, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
