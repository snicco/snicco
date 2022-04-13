# A PSR-15 middleware for the [snicco/signed-url](https://github.com/snicco/signed-url) library

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://app.codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/SignedUrlPsr15Bridge/index.html)

This package consists of two simple middlewares that will make working with `snicco/signed-url` a breeze. Make sure to
read the general [documentation](https://github.com/snicco/signed-url) to
know how to instantiate the needed collaborators.<br>

## Installation

```shell
composer require snicco/signed-url-psr15-bridge
```

## Usage

Make sure that your favorite framework supports binding middleware on the route level. This middleware should only be
added to a route where you expect signed urls, **not globally.**

### Basic Usage

---

```php
$storage = /* */
$hmac = /* */

$validator = new \Snicco\Component\SignedUrl\SignedUrlValidator($storage, $hmac);

$middleware = new \Snicco\Bridge\SignedUrlPsr15\ValidateSignature(
    $validator,
);
/* Attach $middleware to your route */
```

### Customizing the additional request context.

---

As a second argument you can pass a closure that will receive the current request. Anything you return from this closure
will be taken into account when validating the current request.

**This has to match the request context that you used when the link was created!**
<br>
Using the ip-address at creation and the user-agent at validation will not work and the request will always be
invalidated.

```php
// Same as above.
$validator = /* */
$middleware = new \Snicco\Bridge\SignedUrlPsr15\ValidateSignature(
    $validator,
    function(\Psr\Http\Message\RequestInterface $request) {
        return $request->getHeaderLine('User-Agent');
    }
);

/* Attach $middleware to your route */
```

### Only validate unsafe HTTP methods

---

If a signed-url should be used exactly one time (for `GET` requests) you might run into trouble with certain email clients that preload all
links. In this case you can set the third argument of the middleware to `(bool) true`. The signature will then only be checked
if the request method is one of `[POST, PATCH, PUT, DELETE]`.
**Make sure you route is not accessible with safe request methods if you use this option.**

### Garbage collection

---

Add the `CollectGarbage` middleware to your global middleware groups.

```php
// same as above
$storage = /* */
$psr3_logger = /* */

// value between 0-100
// the percentage that one request through the middleware
// will trigger garbage collection.
$percentage = 4;

$middleware = new \Snicco\Bridge\SignedUrlPsr15\CollectGarbage($percentage, $storage, $logger);

/* Attach $middleware to your route */
```