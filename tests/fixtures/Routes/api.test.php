<?php


    declare(strict_types = 1);

    use Tests\stubs\TestApp;

    $GLOBALS['test']['api_routes'] = true;

    TestApp::get('foo', function () {

        return 'foo endpoint';

    });

    TestApp::get('{bad-endpoint}', function (string $endpoint) {

        return TestApp::response()->make(400)->withBody(
            TestApp::response()->createStream('The endpoint: ' . $endpoint . ' does not exist.')
        );

    });