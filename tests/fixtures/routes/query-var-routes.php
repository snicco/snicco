<?php


    declare(strict_types = 1);

    use PHPUnit\Framework\Assert;
    use Tests\stubs\TestApp;
    use WPEmerge\Routing\Router;

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



        $router->get('teams/{county}/{name}', function () {})
               ->wpquery(function (array $query_vars, $county, $name) {

            return array_merge($query_vars, [$county => $name]);

        });

        $router->get('assert-no-handler-run', function () {
            Assert::fail('Route handler was run.');
        })
               ->wpquery(function () {
            return ['foo' => 'baz'];
        });

        $router->get('do-nothing')->wpquery(function () {
            return ['foo' => 'baz'];
        }, false );

    });