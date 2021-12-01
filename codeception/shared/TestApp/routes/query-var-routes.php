<?php

declare(strict_types=1);

use Snicco\Routing\Router;
use PHPUnit\Framework\Assert;
use Tests\Codeception\shared\TestApp\TestApp;

TestApp::route()->prefix('wpquery')->group(function (Router $router) {
    $router->get('foo', function () {
        return 'FOO_QUERY';
    })
           ->wpquery(function () {
               return [
                   'foo' => 'baz',
               ];
           });
    
    $router->post('post', function () {
        return 'FOO_QUERY';
    })
           ->wpquery(function () {
               return [
                   'foo' => 'baz',
               ];
           });
    
    $router->get('teams/{county}/{name}', function (string $country, string $name) {
        return $country.'.'.$name;
    })
           ->wpquery(function ($country, $name) {
               return [$country => $name];
           });
    
    $router->get('assert-no-driver-run', function () {
        Assert::fail('Route driver was run.');
    })
           ->wpquery(function () {
               return ['foo' => 'baz'];
           });
    
    $router->get('do-nothing', function () {
        return 'DID SOMETHING';
    })
           ->wpquery(function () {
               return ['foo' => 'baz'];
           }, false);
});