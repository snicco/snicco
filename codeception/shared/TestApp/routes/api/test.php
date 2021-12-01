<?php

declare(strict_types=1);

use Tests\Codeception\shared\TestApp\TestApp;

$GLOBALS['test']['api_routes'] = true;

TestApp::get('foo', function () {
    return 'foo endpoint';
});

TestApp::get('/{badendpoint}', function (string $badendpoint) {
    return TestApp::response()->make(400)->withBody(
        TestApp::response()->createStream('The endpoint: '.$badendpoint.' does not exist.')
    );
});