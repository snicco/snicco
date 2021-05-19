<?php


    declare(strict_types = 1);

    use Tests\stubs\Conditions\IsPost;
    use Tests\stubs\TestApp;

    $router = TestApp::route();

    $router->get('foo', function () {

        return 'foo';

    });

    $router->get()->where(IsPost::class, true)->handle(function () {

        return 'FOO';

    });

    TestApp::get('get', function () {

        return 'get';
    });

    TestApp::post('post', function () {

        return 'post';
    });

    TestApp::delete('delete', function () {

        return 'delete';
    });

    TestApp::options('options', function () {

        return 'options';
    });

    TestApp::put('put', function () {

        return 'put';
    });

    TestApp::patch('patch', function () {

        return 'patch';
    });

    TestApp::match( ['GET','POST'], 'match', function () {

        return 'match';
    });
