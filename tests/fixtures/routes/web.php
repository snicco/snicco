<?php

declare(strict_types=1);

use Tests\stubs\TestApp;
use Snicco\Http\ResponseFactory;
use Tests\fixtures\Conditions\IsPost;
use Tests\fixtures\Middleware\WebMiddleware;
use Tests\fixtures\Middleware\FooMiddleware;
use Tests\fixtures\Middleware\FooBarMiddleware;

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
    
           return 'get_fallback';
    
       });

$router->post()
       ->where(IsPost::class, $pass_condition)
       ->handle(function () {
    
           return 'post_fallback';
    
       });

$router->patch()
       ->where(IsPost::class, $pass_condition)
       ->handle(function () {
    
           return 'patch_fallback';
    
       })
       ->middleware(WebMiddleware::class);

$router->get('/null', function (ResponseFactory $response_factory) {
    
    return $response_factory->null()->withHeader('foo', 'bar');
    
});

$router->get('/delegate', function (ResponseFactory $response_factory) {
    
    return $response_factory->delegateToWP()->withHeader('foo', 'bar')
                            ->withBody($response_factory->createStream('foo'));
    
});


