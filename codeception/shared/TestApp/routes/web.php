<?php

declare(strict_types=1);

use Snicco\Http\ResponseFactory;
use Tests\Core\fixtures\Conditions\IsPost;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\WebMiddleware;
use Tests\Core\fixtures\Middleware\FooBarMiddleware;

$pass_condition = $GLOBALS['test']['pass_fallback_route_condition'] ?? false;

$router = TestApp::route();

$router->get('foo', function () {
    return 'foo';
});

$router->get('foo_middleware', function () {
    return 'foo';
})
       ->middleware([FooMiddleware::class, FooBarMiddleware::class]);

$router->get()
       ->where(IsPost::class, $pass_condition)
       ->handle(function () {
           return 'get_condition';
       });

$router->post()
       ->where(IsPost::class, $pass_condition)
       ->handle(function () {
           return 'post_condition';
       });

$router->patch()
       ->where(IsPost::class, $pass_condition)
       ->handle(function () {
           return 'patch_condition';
       })
       ->middleware(WebMiddleware::class);

$router->get('/null', function (ResponseFactory $response_factory) {
    return $response_factory->null()->withHeader('foo', 'bar');
});

$router->get('/delegate', function (ResponseFactory $response_factory) {
    return $response_factory->delegateToWP()->withHeader('foo', 'bar')
                            ->withBody($response_factory->createStream('foo'));
});

$router->match(['GET', 'POST'], '/csrf', function () {
    return 'CSRF_CHECK_PASSED';
});

if (isset($GLOBALS['test']['include_fallback_route'])
    && $GLOBALS['test']['include_fallback_route']) {
    $router->fallback(function () {
        return 'FALLBACK';
    });
}