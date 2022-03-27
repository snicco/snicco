# A powerful and customizable error handler for PSR-7/PSR-15 applications

[![codecov](https://img.shields.io/badge/Coverage-100%25-success)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/Psr7ErrorHandler/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

The **ErrorHandler** component of the [**Snicco** project](https://github.com/sniccowp/sniccowp) is a standalone error
handler for **PHP** applications that use **PSR-7** requests.

## Table of contents

1. [Installation](#installation)
2. [Needed collaborators](#usage)
    1. [RequestAwareLogger](#the-requestawarelogger)
    2. [ExceptionInformationProvider](#the-exceptioninformationprovider)
    3. [ExceptionDisplayer](#the-exceptiondisplayer)
3. [Full example](#full-working-example)
4. [Exception utilities](#exception-utilities)
5. [Contributing](#contributing)
6. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
7. [Security](#security)

## Installation

```shell
composer require snicco/psr7-error-handler
```

## Collaborators for the HTTP error handler

On a high level, the [`HttpErrorHandler` interface](src/HttpErrorHandler.php) is responsible for transforming instances
of `Throwable` into instances of `ResponseInterface`.

This package provides a [`TestErrorHandler`](src/TestErrorHandler.php), which just re-throws exceptions and
a [`ProductionErrorHandler`](src/ProductionErrorHandler.php), which is the main focus of this documentation.

To instantiate the [`ProductionErrorHandler`](src/ProductionErrorHandler.php), **we need the following collaborators:**

---

### The `RequestAwareLogger`

The [`RequestAwareLogger`](src/Log/RequestAwareLogger.php) is a simple wrapper class around a **PSR-3 logger**.

It allows you to add [log context](https://www.php-fig.org/psr/psr-3/#13-context) to each log-entry depending on the
caught exception, the current request etc.

The last argument passed to `RequestAwareLogger::__construct` is variadic and accepts instances
of [`RequestLogContext`](src/Log/RequestLogContext.php).

This is how you use it:

(Monolog is just an example, you can use any PSR-3 logger.)

```php
use Psr\Log\LogLevel;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;
use Snicco\Component\Psr7ErrorHandler\Log\RequestLogContext;

$monolog = new Monolog\Logger();

$request_aware_logger = new RequestAwareLogger($monolog);

// With custom exception levels.
$request_aware_logger = new RequestAwareLogger($monolog, [
    Throwable::class => LogLevel::ALERT    
])

// With custom log context:
class AddIPAddressFor403Exception implements RequestLogContext {

    public function add(array $context, ExceptionInformation $information) : array{
        
        if(403 === $information->statusCode()) {
            $context['ip'] = $information->serverRequest()->getServerParams()['REMOTE_ADDR'];
        }
        return $context;
    }
}

// The last argument is variadic.
$request_aware_logger = new RequestAwareLogger($monolog, [], new AddIPAddressFor403Exception());
```

--- 

### The `ExceptionInformationProvider`

The [`ExceptionInformationProvider`](src/Information/ExceptionInformationProvider.php) is responsible for converting
instances of `Throwable` to an instance of [`ExceptionInformation`](src/Information/ExceptionInformation.php).

[`ExceptionInformation`](src/Information/ExceptionInformation.php) is a **value object** that consist of:

- a unique identifier for the exception that will be passed as log context and can be displayed to the user.
- an HTTP status code that should be used when displaying the exception.
- a safe title for displaying the exception (safe meaning "does not contain sensitive information").
- a safe message for displaying the exception (safe meaning "does not contain sensitive information").
- the original `Throwable`
- a transformed `Throwable`
- the original instance of `ServerRequestInterface`

This package comes with
a [`InformationProviderWithTransformation`](src/Information/InformationProviderWithTransformation.php) implementation of
that interface.

You can instantiate this class like so:

```php

use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProviderWithTransformation;

// uses spl_object_hash to uniquely identify exceptions.
$identifier = new SplHashIdentifier();

// This will use the error messages in /resources/en_US.error.json
$information_provider = InformationProviderWithTransformation::fromDefaultData($identifier);

// Or with custom error messages
$error_messages = [
    // The 500 status code is mandatory. All other HTTP status codes are optional.
    500 => [
        'title' => 'Whoops, this did not work...',
        'message' => 'An error has occurred... We are sorry.'
    ];
]
$information_provider = new InformationProviderWithTransformation($error_messages, $identifier);
```

As its class name suggests,
the [`InformationProviderWithTransformation`](src/Information/InformationProviderWithTransformation.php)
allows you to transform exceptions into other kinds of exceptions.

This is done by using [`ExceptionTransformers`](src/Information/ExceptionTransformer.php).

An example on how to transform custom exception classes to an instance of [`HttpException`](src/HttpException.php).

```php
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionTransformer;

class CustomAuthenticationTo404Transformer implements ExceptionTransformer {
    
    public function transform(Throwable $e) : Throwable{
        
        if(!$e instanceof MyCustomAuthenticationException) {
            return $e;
        }
        
        // Key value pairs of headers that will later be added to the PSR-7 response.
        $response_headers = [
            'WWW-Authenticate' => '/login'    
        ];
        
        // The status code that should be used for the PSR-7 response.
        $status_code = 401;
        
        return \Snicco\Component\Psr7ErrorHandler\HttpException::fromPrevious($e, $status_code, $response_headers);
    }
}

$identifier = new SplHashIdentifier();

$information_provider = InformationProviderWithTransformation::fromDefaultData(
    $identifier,
    new CustomAuthenticationTo404Transformer() // Last argument is variadic
);

```

If you provide no [`ExceptionTransformers`](src/Information/ExceptionTransformer.php) every exception will be converted
to a [`HttpException`](src/HttpException.php) with status code `500`. (unless it's already an instance
of [`HttpException`](src/HttpException.php))

---

### The `ExceptionDisplayer`

An [`ExceptionDisplayer`](src/Displayer/ExceptionDisplayer.php) is responsible for
displaying [`ExceptionInformation`](src/Information/ExceptionInformation.php).

An [`ExceptionDisplayer`](src/Displayer/ExceptionDisplayer.php) has one content-type that it supports.

The [`ProductionExceptionHandler`](src/ProductionErrorHandler.php) accepts one or
more [`ExceptionDisplayers`](src/Displayer/ExceptionDisplayer.php)
and will determine the best displayer for the current request.

This package comes with two default displayers that will be used as a fallback:

- [`FallbackHtmlDisplayer`](src/Displayer/FallbackHtmlDisplayer.php), for requests where the `Accept` header
  is `text/html`.
- [`FallbackJsonDisplayer`](src/Displayer/FallbackJsonDisplayer.php), for requests where the `Accept` header
  is `application/json`.

The best displayer for the exception/request is determined by
using [`DisplayerFilters`](src/DisplayerFilter/DisplayerFilter.php).

Out of the box this package comes with the following filters:

- [`Delegating`](src/DisplayerFilter/Delegating.php), delegates to other filters.
- [`CanDisplay`](src/DisplayerFilter/CanDisplay.php), filters based on the return value
  of `ExceptionDisplayer::canDisplay()`
- [`Verbosity`](src/DisplayerFilter/Verbosity.php), filters based on the return value
  of `ExceptionDisplayer::isVerbose()` and the verbosity level during the current request.
- [`ContentType`](src/DisplayerFilter/ContentType.php), filters based on the return value
  of `ExceptionDisplayer::supportedContentType()` and the `Accept` header of the current request. **! Important: This
  filter only performs very basic content negotiation.** Content-negotiation is out of scope for this package and should
  be performed in a middleware.

## Full working example

This is a working example of how you would instantiate the [`ProductionErrorHandler`](src/ProductionErrorHandler.php),
preferably in your dependency-injection container.

```php
use Psr\Log\LogLevel;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\CanDisplay;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\ContentType;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Delegating;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Verbosity;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProviderWithTransformation;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;
use Snicco\Component\Psr7ErrorHandler\ProductionErrorHandler;

// Use any PSR-7 response factory
$psr_7_response_factory = new Nyholm\Psr7\Factory\Psr17Factory();

$request_aware_logger = new RequestAwareLogger(
    new Monolog\Logger(), // Use any PSR-3 logger
);

$information_provider = InformationProviderWithTransformation::fromDefaultData(
    new SplHashIdentifier()
);

$prefer_verbose = (bool) getenv('APP_DEBUG');

$displayer_filter = new Delegating(
    new ContentType(),
    new Verbosity($prefer_verbose),
    new CanDisplay(),
);

$error_handler = new ProductionErrorHandler(
    $psr_7_response_factory,
    $request_aware_logger,
    $information_provider,
    $displayer_filter,
    // Custom exception displayers go here (variadic)
)
```

Then use the instantiated error handler in a middleware like so:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;

class ErrorHandlerMiddleware implements MiddlewareInterface {
    
    private HttpErrorHandler $error_handler;
    
    public function __construct(HttpErrorHandler $error_handler) {
        $this->error_handler = $error_handler;
    }
    
    public function process(ServerRequestInterface $request,RequestHandlerInterface $handler) : ResponseInterface{
        
        try {
            return $handler->handle($request);
        }catch (Throwable $e) {
            return $this->error_handler->handle($e, $request);
        }
    }
    
}

```

## Exception utilities

### User-facing exceptions

This package comes with a [`UserFacing`](src/UserFacing.php) interface, which your custom exceptions can implement.

If an exception is thrown, that implements [`UserFacing`](src/UserFacing.php), the return values of
`UserFacing::safeTitle()` and `UserFacing::safeMessage()` will be used to create
the [`ExceptionInformation`](src/Information/ExceptionInformation.php), instead of the default HTTP error messages that
might not make sense to your users.

**The original exception message will be logged** while your users get to see something they can relate (a little more)
to.

### HTTP exceptions

This packages comes with a generic [`HTTPException`](src/HttpException.php) class that you can throw in your HTTP
related code (mostly middleware).

This allows you to dictate the HTTP response code and optionally additional response headers.

The difference between using the [`HTTPException`](src/HttpException.php) class and using
an [`ExceptionTransformer`](src/Information/ExceptionTransformer.php) is that the latter is intended for your **domain
exceptions** while [`HTTPExceptions`](src/HttpException.php) should be thrown only in HTTP related code (like middleware
and Controllers).

## Contributing

This repository is a read-only split of the development repo of the
[**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
