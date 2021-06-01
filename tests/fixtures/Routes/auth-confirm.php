<?php


    declare(strict_types = 1);

    use Tests\stubs\TestApp;
    use WPEmerge\Routing\Router;

    TestApp::route()->prefix('auth-confirm')->name('auth.confirm')->group( function (Router $router)  {

        $router->get('foo', function () {

            return 'Access to foo granted';

        })->middleware('confirm.auth');

    });