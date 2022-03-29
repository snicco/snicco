# WordPress nonce middleware for [`snicco/http-routing`](https://github.com/sniccowp/http-routing)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/WPNonce/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This middleware for the [`snicco/http-routing`](https://github.com/sniccowp/http-routing) component will eliminate your **WordPress** nonce problems once and for all.

Stop validating nonces manually in each controller.

Stop forgetting to validate nonces.

Stop coupling your controller code to your views through nonce actions.

There is a better way.

## Installation

```shell
composer require snicco/wp-nonce-middleware
```

## Usage

Add the [`VerifyWPNonce`](src/VerifyWPNonce.php) middleware to your global middleware.

This middleware does the following for every request:

- Unsafe requests (`POST`, `PATCH`, `DELETE`, etc) will be checked for a valid **WordPress** nonce in the request body using [`wp_verify_nonce`](https://developer.wordpress.org/reference/functions/wp_verify_nonce/). If no valid nonce is found a `401 HTTPException` will be thrown.
- For READ requests and instance of [`WPNonce`](src/WPNonce.php) will be added to the view data if the returned response is a `ViewResponse`.

In your views you can use the  [`WPNonce`](src/WPNonce.php) instance like so:

**Posting to the same location where the form is located:**

```php
<?php
/**
* @var Snicco\Middleware\WPNonce\WPNonce $wp_nonce 
*/
?>

<form method="POST">
    <?= $wp_nonce() ?>
    <button type="submit">Submit</button>
</form>
```

**Posting to a route url or hard-coded url that is different from the current location:**

```php
<?php
/**
* @var Snicco\Middleware\WPNonce\WPNonce $wp_nonce 
* @var Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator $url 
*/
$route_url = $url->toRoute('route1', ['param1' => 'foo']);

?>

<form method="POST" action="<?= $route_url ?>">
    <?= $wp_nonce($route_url) ?>
    <button type="submit">Submit</button>
</form>
```

Now forget about **WordPress** nonces forever. If a request reaches your controller it has a valid nonce. 

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
