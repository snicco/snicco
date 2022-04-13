# Snicco - TestingBundle

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/TestingBundle/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

The testing bundle provides a full testing framework for **WordPress** applications based on the [`snicco/kernel`](https://github.com/snicco/kernel) library.

## Installation

```shell
composer install snicco/testing-bundle
```

## Usage

This package provides a [`WebTestCase`](src/Functional/WebTestCase.php) that can be used for functional
tests of your application. It's based on [`lucatume/wp-browser`](https://github.com/lucatume/wp-browser) and [`symfony/browser-kit`](https://github.com/symfony/browser-kit).

```php
use Snicco\Bundle\Testing\Functional\Browser;
use Snicco\Bundle\Testing\Functional\WebTestCase;

class SomeTest extends WebTestCase {

    protected function extensions() : array{
        return []; // Return an array of class names implementing TestExtension 
    }
    
    protected function createKernel(){
        return '/path/to/kernel-bootstrap.php' // Path to kernel bootstrap file (assuming this file returns a closure).
    }
    
    public function testHomePage(){
        
        /** @var Browser $browser */
        $browser = $this->getBrowser();
              
        $browser->request('/');
                
        $browser->lastResponse()
                ->assertOk()
                ->assertSeeText('Some text');
                
        $browser->lastDOM()->assertSelectorExists('body > h1');
        
        $browser->reload();
        
        $browser->back();
        
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

If you discover a security vulnerability within **BetterWPCache**, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
