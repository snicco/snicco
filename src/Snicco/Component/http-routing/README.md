# Snicco HTTP-Routing: A PSR7-/PSR-15 routing system and middleware-dispatcher for legacy CMSs

[![codecov](https://img.shields.io/badge/Coverage-100%25-success)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/HttpRouting/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

The **HTTP-Routing** component of the [**Snicco project**](https://github.com/snicco/snicco) is an opinionated
library that combines a routing system built upon [`FastRoute`](https://github.com/nikic/FastRoute) with a power PSR-15
middleware dispatcher.

Although not a requirement, **it was intentionally built to support legacy CMSs like WordPress** where you don't have
full control of the request-response lifecycle.

Features:

- Rich API to configure routes
- URL generation / reverse routing
- Attaching middleware on a per-route basis
- Route groups
- Completely cached in production
- Special handling for the admin area of a legacy CMS (if applicable)
- and much more.

## Table of contents

1. [Installation](#installation)
2. [Routing](#routing)
    1. [Creating a router](#creating-a-router)
    2. [Defining routes](#defining-routes)
        1. [Defining HTTP verbs](#defining-http-verbs)
        2. [Route parameters](#route-parameters)
        3. [Regex constraints](#regex-constraints)
        4. [Adding middleware](#adding-middleware)
        5. [Adding conditions](#adding-conditions)
        6. [Route groups](#route-groups)
        7. [Controllers](#controllers)
        8. [Redirect routes](#redirect-routes)
        9. [View routes](#view-routes)
        10. [Admin routes](#admin-routes)
        11. [API routes](#api-routes)
        12. [Route caching](#route-caching)
    3. [Matching a route](#matching-a-route)
    4. [Reverse routing / URL generation](#reverse-routing--url-generation)
    5. [The Admin menu](#the-adminmenu)
3. [PSR-15 middleware dispatcher](#psr-15-middleware-dispatcher)
   1. [Creating a middleware pipeline](#creating-a-middleware-pipeline)
   2. [Piping requests](#piping-requests)
   3. [Middleware resolver](#middlewareresolver)
   4. [PSR utilities](#psr-utilities)
5. [Contributing](#contributing)
6. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
7. [Security](#security)

## Installation

```shell
composer require snicco/http-routing
```

## Routing

### Creating a router

The central class of the routing subcomponent is the [`Router`](src/Routing/Router.php) facade class. (no, not a laravel
facade)

The [`Router`](src/Routing/Router.php) serves as a factory for different parts of the routing system.

To instantiate a [`Router`](src/Routing/Router.php) we need the following collaborators:

- The [`URLGenerationContext`](src/Routing/UrlGenerator/UrlGenerationContext.php), which is a **value object** that
  configures the URL generation.
- A [`RouteLoader`](src/Routing/RouteLoader/RouteLoader.php), which is responsible for loading and configuring your
  routes (only if nothing is cached yet.)
- A [`RouteCache`](src/Routing/Cache/RouteCache.php), which is responsible for caching the route definitions in
  production.
- An instance of [`AdminArea`](src/Routing/Admin/AdminArea.php), which serves as a bridge between the routing system and
  a legacy CMS admin area.

```php
use Snicco\Component\HttpRouting\Routing\Cache\CallbackRouteCache;
use Snicco\Component\HttpRouting\Routing\Cache\NullCache;
use Snicco\Component\HttpRouting\Routing\RouteLoader\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoader\PHPFileRouteLoader;
use Snicco\Component\HttpRouting\Routing\Router;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;

$context = new UrlGenerationContext('snicco.io');

$route_loading_options = new DefaultRouteLoadingOptions(
    '/api/v1' // the base-prefix for API routes
);
$route_loader = new PHPFileRouteLoader(
    [__DIR__.'/routes'], // directories of "normal" routes
    [__DIR__.'/routes/api'], // directories of "API" routes, optional
    $route_loading_options,
);

// In development
$route_cache = new NullCache();
// In production
$route_cache = new CallbackRouteCache(function ($load_routes) {
    $cached = false; // use your cache here.
    if ($cached) {
        return $cached;
    }
    
    $store_me = $load_routes();
        
    return $store_me;
});

$router = new Router(
     $context,
     $route_loader,
     $route_cache
//     $admin_area  This is a simple interface that you can implement if you use admin routes.
);

```

Once we have our [`Router`](src/Routing/Router.php), we can use it to instantiate the different parts of the routing
system.

```php
use Snicco\Component\HttpRouting\Routing\Router;

/**
* @var Router $router 
*/
$router = /* */

$router->routes(); // Returns an instance of RouteCollection

$router->urlGenerator(); // Returns an instance of UrlGenerator

$router->urlMatcher(); // Returns an instance of UrlMatcher

$router->adminMenu(); // Returns an instance of AdminMenu

```

---

### Defining routes

The included [`PHPFileRouteLoader`](src/Routing/RouteLoader/PHPFileRouteLoader.php) will search for files with a `.php`
extension inside each of the provided route directories. Nested directories are not used.

For now, we assume the following directory structure:

```
your-project-root
├── routes/
│   ├── frontend.php
│   ├── admin.php
├── api-routes/
│   ├── v1.php
└── ...
```

Each file inside a route directory must return a closure that accepts an instance
of [`RoutingConfigurator`](src/Routing/RoutingConfigurator/RoutingConfigurator.php)

```php
// ./routes/frontend.php
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $configurator ) {
    //
}
```

A `admin.php` route file is a special case. It will receive an instance
of [`AdminRoutingConfigurator`](src/Routing/RoutingConfigurator/AdminRoutingConfigurator.php).

```php
// ./routes/admin.php
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;

return function (AdminRoutingConfigurator $configurator ) {
    //
}
```

The [`RouteLoadingOptions`](src/Routing/RouteLoader/RouteLoadingOptions.php) **value object** allows you to customize
some generic settings for all routes like automatically adding a [middleware](#adding-middleware) with the name of the
route file.

Check out the [`DefaultRouteLoadingOptions`](src/Routing/RouteLoader/DefaultRouteLoadingOptions.php) for an example.

#### Defining HTTP verbs

```php
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

/**
* @var WebRoutingConfigurator $configurator 
*/
$configurator = /* */

$configurator->get(
   'posts.index', // The route name MUST BE UNIQUE.
   '/posts', // The route pattern
   [PostController::class, 'index'] // The controller for the route.
);

$configurator->post('posts.create', '/posts', [PostController::class, 'create']);

$configurator->put('posts.update', '/posts/{post_id}', [PostController::class, 'update']);

$configurator->delete('posts.delete', '/posts/{post_id}', [PostController::class, 'delete']);

$configurator->patch(/* */);

$configurator->options(/* */);

$configurator->any(/* */);

$configurator->match(['GET', 'POST'], /* */);

```

---

#### Route parameters

The syntax of **HTTP-Routing** component offers an alternative syntax to the native syntax
of [`FastRoute`](https://github.com/nikic/FastRoute#defining-routes). This is highly opinionated, but we think that the
syntax of [`FastRoute`](https://github.com/nikic/FastRoute#defining-routes) is a little to verbose, especially when
dealing with optional segments and regex requirements.

**! For maximum performance, all routes will be compiled to match the native syntax of FastRoute before caching.**

- Route segments enclosed within `{...}` are required.
- Route segments enclosed within `{...?}` are optional.

```php
$configurator->get(
   'route_name',
   '/posts/{post}/comments/{comment?}',
   PostController::class
);
```

The above route definition will match `/posts/1/comments/2` and `/posts/1/comments`.

The captured parameters will be available to the configured controller.

Trailing slashes can be used in combination with route segments.

```php
$configurator->get(
   'route_name',
   '/posts/{post}/comments/{comment?}/',
   PostController::class
);
```

The above route definition will match `/posts/1/comments/2/` and `/posts/1/comments/`.

**Optional segments can only occur at the end of a route pattern.**

---

#### Regex constraints

```php
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

/**
* @var WebRoutingConfigurator $configurator 
*/
$configurator->get(
   'route1',
   '/user/{id}/{name}',
    PostController::class
)->requirements([
    'id' => '[0-9]+',
    'name' => '[a-z]+'
]);

/** @var Route $route */
$route = $configurator->get(/* */);

// The Route class contains a couple of helper methods.
$route->requireAlpha('segment_name');
$route->requireNum('segment_name');
$route->requireAlphaNum('segment_name');
$route->requireOneOf('segment_name', ['category-1', 'category-2']);
```

---

#### Adding Middleware

Middleware can be configured for each route individually.

A middleware can either be the fully qualified class name of
a [PSR-15 middleware](https://www.php-fig.org/psr/psr-15/#22-psrhttpservermiddlewareinterface)
or an alias that will later be resolved to the class name of
a [PSR-15 middleware](https://www.php-fig.org/psr/psr-15/#22-psrhttpservermiddlewareinterface).

Arguments can be passed to middleware (the constructor) as a comma separated list after a `:`.
The following conversions are performed before instantiating a middleware with the passed arguments:

- (string) true => (bool) true
- (string) false => (bool) false
- (string) numeric => numeric


```php
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

/**
* @var WebRoutingConfigurator $configurator 
*/
$configurator->get('route1', '/route1', InvokableController::class)
              // middleware as an alias.
             ->middleware('auth')
             
             // adding multiple middleware
             ->middleware([PSR15MiddlewareOne::class, PSR15MiddlewareTwo::class]);
             
             // passing comma separated arguments
             ->middleware('can:manage_options,1');
```

---

#### Adding conditions

In addition to matching a route by its URL pattern, you can also specify route conditions.

A route condition is any class that implements [`RouteCondition`](src/Routing/Condition/RouteCondition.php).

```php
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

/**
* @var WebRoutingConfigurator $configurator 
*/
$configurator->get('route1', '/route1', InvokableController::class)
             
             ->condition(OnlyIfUserAgentIsFirefox::class)
                
             // passing arguments   
             ->condition(OnlyIfHeaderIsPresent::class, 'X-CUSTOM-HEADER');
```

---

#### Route groups

You can group routes with similar attributes together using route groups.

The following attributes can currently be grouped in some form:

- middleware: will be merged for all routes in the group.
- url prefix: will be added to all URL patterns in the group.
- route name: will be concatenated with a `.` for all routes.
- namespace: will be set for all routes, but can be overwritten on a per-route basis.

**Nested route groups are supported.**

```php
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

/**
* @var WebRoutingConfigurator $configurator 
*/
$configurator
    ->name('users')
    ->prefix('/base/users')
    ->middleware('auth')
    ->namespace('App\\Http\\Controllers')
    ->group(function (WebRoutingConfigurator $configurator) {
        
        // The route name will be users.profile
        // The route pattern will be /base/users/profile/{user_id}
        // The controller definition will be [App\\Http\\Controllers\\ProfileController::class, 'index']
        // The middleware is [auth, auth-confirmed] 
        $configurator->get('profile', '/profile/{user_id}', 'ProfileController@index')
                     ->middleware('auth-confirmed');
            
        $configurator->/* */->group(/* */);    
    });

```

---

#### Controllers

The controller is the class method that is attached to route.

The controller will be used to transform a
[ **PSR-7** server request](https://www.php-fig.org/psr/psr-7/#15-server-side-requests) to a
[**PSR-7** response](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface).
(more on that later)

For now, its only important how to define controllers and which arguments will be available in controllers.

```php
namespace App\Controller;

use Snicco\Component\HttpRouting\Http\Psr7\Request;

class RouteController {
    
    public function __invoke(Request $request){
        //
    }
    
    public function withoutRequest(string $route_param){
        //
    }
        
    public function withRequestTypehint(Request $request, string $route_param){
        //
    }
        
}

// Valid ways to define a controller:

$configurator->get('route1', '/route-1', RouteController::class)

$configurator->get('route2', '/route-2/{param}', [RouteController::class, 'withoutRequest']);

$configurator->get('route3', '/route3/{param}', 'App\\Controller\\RouteController@withRequestTypehint');
// or
$configurator->namespace('App\\Controller')->get('route3', '/route3/{param}', 'RouteController@withRequestTypehint');

```

If a controller is defined using the fully qualified class name it must have an `__invoke` method.

It's possible to leave out the controller, in which case
a [fallback controller](src/Controller/DelegateResponseController.php) will be added to the route. The fallback
controller will always return an instance of [`DelegatedResponse`](src/Http/Response/DelegatedResponse.php) which can be
used to express (to another system) that the current request should not be handled (by your code).

The first argument passed to all controller methods is an instance
of `Snicco\Component\HttpRouting\Http\Psr7\Request` (**if the controller method has that typehint**).

Captured route segments are passed **by order** to the controller method. Method parameter names and segment names in
the route definition are not important.

**Captured route segments are always strings (in FastRoute), but numerical values are converted to integers for
convenience.**

[Route conditions](#adding-conditions) can also return "captured parameters". If a route has a condition that returned
parameters, they will be passed to the controller methods after the parameters that were captured in the URL.

--- 

#### Redirect routes 

You can directly configure redirects in your route file. Instead of defining a dedicated controller
all redirect routes will use the [`RedirectController`](src/Controller/RedirectController.php) to directly create
a [`RedirectResponse`](src/Http/Response/RedirectResponse.php).

```php
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

/**
* @var WebRoutingConfigurator $configurator
*/

$configurator->redirect('/foo', '/bar', 301);

$configurator->redirectAway('/foo', 'https://external-site.com', 302);

$configurator->redirectToRoute('/foo', 'route1', 307);
```

---

#### View routes

If you only want to return a simple template for a given URL without much logic
you can use the `view()` method on the routing configurator.

```php
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

/**
* @var WebRoutingConfigurator $configurator
*/

$configurator->view('/contact', 'contact.php');
```

When this route matches it will return an instance of [`ViewResponse`](src/Http/Response/ViewResponse.php).
It's up to you how to convert this into the underlying template. You will probably want to use your favorite 
template engine inside a custom middleware to achieve this.

---

#### Admin routes

Routes defined in a `admin.php` file are special in a sense that they can be used to create routes to the admin area of
a CMS like **WordPress** where you usually don't have control ofter the "routing".

You can even create admin menu items directly from your route definitions.

All of these implementation details are abstracted away by the [`AdminArea`](src/Routing/Admin/AdminArea.php) interface
and the [`AdminMenu`](src/Routing/Admin/AdminMenu.php) interface.

Admin routes are configured by using
the [`AdminRoutingConfigurator`](src/Routing/RoutingConfigurator/AdminRoutingConfigurator.php).

Admin routes are limited to `GET` requests.

Instead of using the `WebRoutingConfigurator::get()` method you'll use the `AdminRoutingConfigurator::page()`
and `AdminRoutingConfigurator::subPage()` methods.

The following is an example on how you would use this in **WordPress** where routing in the admin area is done by using
a `page` query variable. Check out the [`WPAdminArea`](src/Routing/Admin/WPAdminArea.php) for the **WordPress**
implementation of the [`AdminArea`](src/Routing/Admin/AdminArea.php) interface.

```php
// .routes/admin.php
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenuItem;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;

/**
* @var AdminRoutingConfigurator $configurator
*/

$configurator->name('my-plugin')
             ->middleware('my-plugin-middleware')
             ->group(function (AdminRoutingConfigurator $configurator) {
             
                $parent_route = $configurator
                   ->page('overview', '/admin.php/overview', OverViewController::class)
                   ->middleware('parent-middleware');
                
                $configurator->page(
                   'settings', 
                   '/admin.php/settings', 
                   SettingsController::class, 
                   [
                      // Explicitly configure menu item attributes.
                      AdminMenuItem::MENU_TITLE => 'Custom settings title'
                   ]
                   $parent_route // Set a parent route to create a menu hierarchy. Middleware is inherited.
                );
                
             });
```

In your default **WordPress** installation these routes would match the following path:

- `/wp-adming/admin.php?page=overview`
- `/wp-adming/admin.php?page=settings`

Based on your route name and route pattern an instance of [`AdminMenuItem`](src/Routing/Admin/AdminMenuItem.php) will
automatically be added to the [`AdminMenu`](src/Routing/Admin/AdminMenu.php) that
is [available through the `Router`](#creating-a-router).

---

#### API Routes

The difference between route files inside the [api route directory](#creating-a-router) and "normal" routes is that
the `RouteLoadingOptions::getApiRouteAttributes()` method will be used to apply default settings for each route.

This allows for example:

- adding a base prefix like `/api` to all routes
- prefixing route names with `api.`
- parsing a version number from the filename and applying it as a prefix `/api/v1`

**Using API-routes is completely optional.**

---

#### Route caching

**Everything** that was mentioned above will be cached in production into a single PHP file that can be returned very
fast by [OPcache](https://www.php.net/manual/de/book.opcache.php).

For that exact reason this package intentionally does not support `Closures` as a "route controller". `Closures` can't
be serialized natively in **PHP**.

Internally, `FastRoute` only contains the names of each route. Once a route is matched that single route only will be
hydrated and "run".

This provides a significant performance increase as the number of routes in your application grows.

Check out the [`SerializedRouteCollection`](src/Routing/Route/SerializedRouteCollection.php) for details.

---

### Matching a route

The first call to `Router::urlMatcher()` will lazily load and configure all routes (or return the cached ones).

```php
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;

$router = /* */

/** @var  $url_matcher */
$url_matcher = $router->urlMatcher();

$psr_server_request = /* create any psr7 server request here. */

$routing_result = $url_matcher->dispatch($psr_server_request);

$routing_result->isMatch();
$routing_result->route();
$routing_result->decodedSegments();
```

---

### Reverse routing / URL generation

Routing systems are always bidirectional:

- URL => Route
- route name + parameters => URL

`FastRoute` only provides the first part. This package fills in that void.

The first call to `Router::urlGenerator()` will lazily load and configure all routes (or return the cached ones).

[Regex constraints](#regex-constraints) are taken into account when generating URLs and provided values that would cause
to not match the route will throw an exception.

```php
use Snicco\Component\HttpRouting\Routing\Router;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

// In a route file:
$configurator->get('route1', '/route1/{param1}/{param2}', RouteController::class)
              ->requireAlpha('param1')
              ->requireNum('param2');

/**
* @var Router $router 
*/
$router = /* */

$url_generator = $router->urlGenerator();

$url = $url_generator->toRoute('route1', ['param1' => 'foo', 'param2' => '1']); 
var_dump($url); // /route1/foo/1

$url = $url_generator->toRoute('route1', ['param1' => 'foo', 'param2' => '1'], UrlGenerator::ABSOLUTE_URL); 
var_dump($url); // https://snicco.io/route1/foo/1 (host and scheme depend on your UrlGenerationContext)


// This will throw an exception because param2 is not a number
$url_generator->toRoute('route1', ['param1' => 'foo', 'param2' => 'bar']); 
```

---

### The AdminMenu

If you are using [admin routes](#admin-routes), an instance of [`AdminMenu`](src/Routing/Admin/AdminMenu.php) will
automatically be configured based on your route definitions.

You can use the [`AdminMenu`](src/Routing/Admin/AdminMenu.php) object to configure some external system of a legacy
CMS (if applicable).

The first call to `Router::adminMenu()` will lazily load and configure all routes (or return the cached ones).

```php
/**
* @var Router $router 
*/
$router = /* */

$admin_menu = $router->adminMenu();

foreach ($admin_menu->items() as $menu_item) {
    // register the menu item somewhere.
}
```

---

## PSR-15 Middleware dispatcher

This package comes with a very powerful PSR-15 middleware dispatcher that already incorporates the configured routing
system.

The central piece is the [`MiddlewarePipeline`](src/Middleware/MiddlewarePipeline.php).

### Creating a middleware pipeline

The middleware pipeline needs a [**PSR-11** container](https://www.php-fig.org/psr/psr-11/) to lazily resolve your
controllers and middleware.

Furthermore, an instance of [`HTTPErrorHanlder`](https://github.com/snicco/psr7-error-handler) is needed to handle
exceptions for each middleware.

```php
use Psr\Container\ContainerInterface;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\Psr7ErrorHandler\ProductionErrorHandler;

/**
* @var ContainerInterface $psr_11_container 
*/
$psr_11_container = /* */

/**
* @var ProductionErrorHandler 
*/
$psr7_error_handler = /* */

$pipeline = new MiddlewarePipeline(
    $psr_11_container,
    $psr7_error_handler
);
```

---

### Piping requests

At a basic level, the [middleware pipeline](#creating-a-middleware-pipeline) takes a
[ **PSR-7** server request](https://www.php-fig.org/psr/psr-7/#15-server-side-requests), pipes it through multiple
[**PSR-15** middleware](https://www.php-fig.org/psr/psr-15/#22-psrhttpservermiddlewareinterface) and returns a
[**PSR-7** response](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface). How you send that response object is up to you.

```php
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;

/**
* @var MiddlewarePipeline $pipeline
*/
$pipeline = /* */

$response = $pipeline
               ->send($server_request)
               ->through([
                   Psr15MiddlewareOne::class,
                   Psr15MiddlewareTwo::class,
                ])->then(function (Request $request) {
                    // Throw exception or return a default response.
                    throw new RuntimeException('Middleware pipeline exhausted without returning response.');
                });
```

To connect the middleware pipeline with our routing system we use to inbuilt **PSR-15** middleware of this package.

- [`RoutingMiddleware`](src/Middleware/RoutingMiddleware.php)
- [`RouteRunner`](src/Middleware/RouteRunner.php)

The [`RoutingMiddleware`](src/Middleware/RoutingMiddleware.php) is responsible for matching the current request in the
pipeline to a route of the [routing system](#routing).

```php
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;

$routing_middleware = new RoutingMiddleware(
    $router->urlMatcher();
);
```

The [`RouteRunner`](src/Middleware/RouteRunner.php) is responsible for "running" the matched route.

If no route was matched an instance of [`DelegatedResponse`](src/Http/Response/DelegatedResponse.php) will be returned.

If a route was matched the following will happen:

- All middleware of the matched route will be resolved.
- A new (inner) middleware pipeline will be created that pipes the request through all route middleware.
- The last step of this inner middleware pipeline will resolve the route controller from the container and execute it.

To instantiate the [`RouteRunner`](src/Middleware/RouteRunner.php) we first need
a [`MiddlewareResolver`](src/Middleware/MiddlewareResolver.php).

```php
use Snicco\Component\HttpRouting\Middleware\RouteRunner;

$pipeline = /* This can be the same pipeline we created initially. The pipeline is immutable anyway. */
$psr_11_container = /* */
$middleware_resolver = /* */

$route_runner = new RouteRunner($pipeline, $middleware_resolver, $psr_11_container);

$response = $pipeline->send($server_request)
                     ->through([
                        $routing_middleware,
                        $route_runner   
                     ])->then(function () {
                        throw new RuntimeException('Middleware pipeline exhausted.');
                     });
```

---

### MiddlewareResolver

As the class name suggests, the [`MiddlewareResolver`](src/Middleware/MiddlewareResolver.php) is responsible for
resolving all middleware that should be applied to an individual route and/or request.

```php
use Snicco\Component\HttpRouting\Middleware\MiddlewareResolver;

// The following four middleware groups can be set to always be applied, even if no route matched.
$always_run = [
    'global'
    'frontend',
    'admin',
    'api'
]

// This configures the short aliases we used in our route definitions
$middleware_aliases = [
    'auth' => AuthenticateMiddleware::class
]

// An alias can also be a middleware group.
// Middleware groups can contain other groups.
$middleware_groups = [
    'group1' => [
        'auth', // group contains alias
        SomePsr15Middleware::class    
    ],
    'group2' => [
        'group1,' // fully contains group1
         SomeOtherPsr15Middleware::class    
    ],
    'global' => [],
    'frontend' => [ ],
    'api' => [
        RateLimitAPI::class    
    ],
    'admin' => []   
];

// A list of class names, the 0-index has the highest priority, meaning that it will
// always run first.
$middleware_priority = [
    SomePsr15Middleware::class,
    SomeOtherPsr15Middleware::class
];

$middleware_resolver = new MiddlewareResolver(
    $always_run,
    $middleware_aliases, 
    $middleware_groups,
    $middleware_priority
);
```

**The middleware resolver can be cached to maximize performance.**

Caching the middleware resolver means, that for each routes in your application the middleware is already resolved
recursively, groups are expanded, aliases are resolved etc.

```php
use Snicco\Component\HttpRouting\Middleware\MiddlewareResolver;

$middleware_resolver = new MiddlewareResolver();

$store_me = $middleware_resolver->createMiddlewareCache(
    $router->routes(),
    $psr_11_container
);

file_put_contents('/path/to/cache-dir/middleware-cache.php', '<?php return ' . var_export($store_me, true) . ';');

list($route_map, $request_map) = require '/path/to/cache-dir/middleware-cache.php';

$cached_resolver = MiddlewareResolver::fromCache($route_map, $request_map);
```

---

### PSR utilities

This package contains some classes that extend the **PSR** interfaces to provide some utility helpers.

Using them is entirely optional:

- The abstract [`Middleware`](src/Middleware/Middleware.php) and the
  abstract [`Controller`](src/Controller/Controller.php) can be extended. They both give you access to
  the [`ResponseUtils`](src/Http/ResponseUtils.php) class and contain a reference to
  the [`URLGenerator`](src/Routing/UrlGenerator/UrlGenerator.php).
- The [`Request`](src/Http/Psr7/Request.php) class wraps any **PSR-7** request and provides some helpful methods not
  defined in the **PSR-7** interface.
- The [`Response`](src/Http/Psr7/Response.php) class wraps any **PSR-7** response and provides some helpful methods
  not defined in the **PSR-7** interface.

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
