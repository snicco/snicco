<?php


    declare(strict_types = 1);

    use Tests\stubs\TestApp;
    use WPEmerge\Routing\Router;

    TestApp::route()->group(['prefix'=>'middleware'], function (Router $router) {

        $router->get('foo', function () {
            return 'foo';
        })->middleware('custom_group');

    });