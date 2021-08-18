<?php

declare(strict_types=1);

use Tests\stubs\TestApp;

$GLOBALS['test']['other_api_routes'] = true;

TestApp::get('foo', function () {
    
    return 'foo other endpoint';
    
});