<?php


    declare(strict_types = 1);

    use Tests\stubs\Conditions\IsPost;
    use Tests\stubs\Middleware\FooBarMiddleware;
    use Tests\stubs\Middleware\FooMiddleware;
    use Tests\stubs\TestApp;

    require __DIR__ . DS . 'query-var-routes.php';
    require __DIR__ . DS . 'aliased-routes.php';

    $router = TestApp::route();

    $router->get('foo', function () {

        return 'foo';

    });

    $router->get('foo_middleware', function () {

        return 'foo';

    })
           ->middleware([FooMiddleware::class,  FooBarMiddleware::class]);

    $router->get()
           ->where(IsPost::class, true)
           ->handle(function () {

        return 'fallback_route';

    });

    $router->post()
           ->where(IsPost::class, false)
           ->handle(function () {

               return 'fallback_route';

           });


