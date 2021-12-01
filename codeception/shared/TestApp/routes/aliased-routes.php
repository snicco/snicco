<?php

declare(strict_types=1);

use Tests\Codeception\shared\TestApp\TestApp;

TestApp::route()->group(function () {
    TestApp::get('get', function () {
        return 'get';
    })->name('get');
    
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
    
    TestApp::match(['GET', 'POST'], 'match', function () {
        return 'match';
    });
}, ['prefix' => 'alias', 'name' => 'alias']);

