# A psr3, psr7 and psr15 compatible middleware for [the sniccowp/signed-url](https://github.com/sniccowp/sniccowp/tree/feature/extract-magic-link/packages/signed-url) library

This package consists of two simple middlewares that will make working with `sniccowp/signed-url` a breeze. Make sure to
read the general [documenation](https://github.com/sniccowp/sniccowp/tree/master/src/Snicco/Component/signed-url) to
know how to instantiate the needed collaborators.<br>

## Installation

```shell
composer require sniccowp/signed-url-middleware
```

## Usage

Make sure that your favorite framework supports binding middleware on the route level. This middleware should only be
added to a route where you expect signed urls, not globally.

### Basic Usage

---

```php
$storage = /* */
$hmac = /* */

$validator = new \Snicco\Component\SignedUrl\SignedUrlValidator($storage, $hmac);

$middleware = new \Snicco\Bridge\SignedUrlMiddleware\ValidateSignature(
    $validator,
);
// Attach $middleware to your route.
/* */
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
$middleware = new \Snicco\Bridge\SignedUrlMiddleware\ValidateSignature(
    $validator,
    function(\Psr\Http\Message\RequestInterface $request) {
        return $request->getHeaderLine('User-Agent');
    }
);

// Attach $middleware to your route.
/* */
```

### Only validate unsafe HTTP methods

---

If a signed-url should be used exactly one time you might run into trouble with certain email clients that preload all
links. In this case you can set the third argument of the middleware to `true`. The signature will then only be checked
if the request method is one of `[POST, PATCH, PUT, DELETE]`.
**Make sure you route is not accessible with safe request methods if you use this option**

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

$middleware = new \Snicco\Bridge\SignedUrlMiddleware\CollectGarbage($percentage, $storage, $logger);

// Attach middleware 
/* */
```