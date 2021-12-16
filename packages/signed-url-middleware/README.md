# A psr3, psr7 and psr15 compatible middleware for [the sniccowp/signed-url](https://github.com/sniccowp/sniccowp/tree/feature/extract-magic-link/packages/signed-url) library

This package consists of two simple middlewares that will make working with `sniccowp/signed-url` a breeze. Make sure to
read the
general [documenation](https://github.com/sniccowp/sniccowp/tree/feature/extract-magic-link/packages/signed-url) to know
how to instantiate the needed collaborators.<br>

## Installation

```shell
composer require sniccowp/signed-url-middleware
```

## Usage

Make sure that your favorite framework supports binding middleware on the route level. This middleware should only be
added to a route where you expect signed urls, not globally.

### Basic Usage

```php
$storage = /* */
$hasher = /* */

$validator = new \Snicco\SignedUrl\SignedUrlValidator($storage, $hasher);

$psr_response_factory = /* */
$psr_logger = /* */

$middleware = new \Snicco\SignedUrlMiddleware\ValidateSignature(
    $validator,
    $psr_response_factory,
    $psr_logger
);
// Attach $middleware to your route.
/* */
```

### Customize the failure response

By default, a 403 response will be returned with a very basic HTML template. You can customize the html output by
passing a callable as the fourth argument. You'll want to wrap your favorite template engine in this closure.

```php
// Same as above.
$validator = /* */
$psr_response_factory = /* */
$psr_logger = /* */

$middleware = new \Snicco\SignedUrlMiddleware\ValidateSignature(
    $validator,
    $psr_response_factory,
    $psr_logger,
    function(\Psr\Http\Message\RequestInterface $request) {
        return "<h1> Your link is invalid </h1> Please contact support."
    }
);
// Attach $middleware to your route.
/* */
```

### Customizing the psr log levels:

By default, invalid attempts will be logged with ` \Psr\Log\LogLevel::WARNING`. You can customize this behaviour by
passing an associative array as the fifth parameter.

```php
// Same as above.
$validator = /* */
$psr_response_factory = /* */
$psr_logger = /* */
$template = /* */

$log_levels = [
  \Snicco\SignedUrl\Exceptions\InvalidSignature::class => \Psr\Log\LogLevel::WARNING,
  \Snicco\SignedUrl\Exceptions\SignedUrlExpired::class => \Psr\Log\LogLevel::INFO,
  \Snicco\SignedUrl\Exceptions\SignedUrlUsageExceeded::class => \Psr\Log\LogLevel::NOTICE,
]


$middleware = new \Snicco\SignedUrlMiddleware\ValidateSignature(
    $validator,
    $psr_response_factory,
    $psr_logger,
    $template,
    $log_levels
);

// Attach $middleware to your route.
/* */
```

#### Customizing the additional request context.

As a sixth argument you can pass a closure that will receive the current request. Anything you return from this closure
will be taken into account when validating the current request.

**This has to match the request context that you used when the link was created!**
<br>
Using the ip-address at creation and the user-agent at validation will not work and the request will always be
invalidated.

```php
// Same as above.
$validator = /* */
$psr_response_factory = /* */
$psr_logger = /* */
$template = /* */
$log_levels = /* */

$middleware = new \Snicco\SignedUrlMiddleware\ValidateSignature(
    $validator,
    $psr_response_factory,
    $psr_logger,
    $template
    $log_levels,
    function(\Psr\Http\Message\RequestInterface $request) {
        return $request->getHeaderLine('User-Agent');
    }
);

// Attach $middleware to your route.
/* */
```

### Garbage collection

Add the `CollectGarbage` middleware to your global middleware groups.

```php
// same as above
$storage = /* */
$logger = /* */

// value between 0-100
// the percentage that one request through the middleware
// will trigger garbage collection.
$percentage = 4;

$middleware = new \Snicco\SignedUrlMiddleware\CollectGarbage($percentage, $storage, $logger);

// Attach middleware 
/* */
```