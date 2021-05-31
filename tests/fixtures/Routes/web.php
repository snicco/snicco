<?php


    declare(strict_types = 1);

    use Tests\fixtures\Conditions\IsPost;
    use Tests\fixtures\Middleware\FooBarMiddleware;
    use Tests\fixtures\Middleware\FooMiddleware;
    use Tests\fixtures\Middleware\WebMiddleware;
    use Tests\stubs\TestApp;

    require __DIR__.DS.'query-var-routes.php';
    require __DIR__.DS.'aliased-routes.php';
    require __DIR__.DS.'middleware-testing-routes.php';

    $router = TestApp::route();

    $router->get('foo', function () {

        return 'foo';

    });

    $router->get('foo_middleware', function () {

        return 'foo';

    })
           ->middleware([FooMiddleware::class, FooBarMiddleware::class]);

    $router->get()
           ->where(IsPost::class, true)
           ->handle(function () {

               return 'get_fallback';

           });

    $router->post()
           ->where(IsPost::class, false)
           ->handle(function () {

               return 'post_fallback';

           });

    $router->patch()
           ->where(IsPost::class, true)
           ->handle(function () {

               return 'patch_fallback';

           })
           ->middleware(WebMiddleware::class);
