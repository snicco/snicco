<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Exception;
use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Tests\Core\RoutingTestCase;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Codeception\shared\TestDependencies\Bar;
use Tests\Core\fixtures\Middleware\MiddlewareWithDependencies;
use Tests\Core\fixtures\Controllers\Admin\AdminControllerWithMiddleware;

/**
 * @todo Tests for parameters + dependencies.
 */
class RouteMiddlewareDependencyInjectionTest extends RoutingTestCase
{
    
    /** @test */
    public function middleware_is_resolved_from_the_service_container()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware(MiddlewareWithDependencies::class);
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobar', $request);
    }
    
    /** @test */
    public function controller_middleware_is_resolved_from_the_service_container()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', AdminControllerWithMiddleware::class.'@handle');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobarbaz:controller_with_middleware', $request);
    }
    
    /** @test */
    public function after_controller_middleware_got_resolved_the_controller_is_not_instantiated_again_when_handling_the_request()
    {
        $GLOBALS['test'][AdminControllerWithMiddleware::constructed_times] = 0;
        
        $this->createRoutes(function () {
            $this->router->get('/foo', AdminControllerWithMiddleware::class.'@handle');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobarbaz:controller_with_middleware', $request);
        
        $this->assertRouteActionConstructedTimes(1, AdminControllerWithMiddleware::class);
    }
    
    /** @test */
    public function middleware_arguments_are_passed_after_any_class_dependencies()
    {
        $this->withMiddlewareAlias([
            'm' => MiddlewareWithClassAndParamDependencies::class,
        ]);
        
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware('m:BAZ,BIZ');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobarBAZBIZ', $request);
    }
    
    /** @test */
    public function a_middleware_with_a_typed_default_value_and_no_passed_arguments_works()
    {
        $this->withMiddlewareAlias([
            'm' => MiddlewareWithTypedDefault::class,
        ]);
        
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return 'foo';
            })->middleware('m:BAZ,BIZ');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    private function assertRouteActionConstructedTimes(int $times, $class)
    {
        $actual = $GLOBALS['test'][$class::constructed_times] ?? 0;
        
        $this->assertSame(
            $times,
            $actual,
            'RouteAction ['
            .$class
            .'] was supposed to run: '
            .$times
            .' times. Actual: '
            .$GLOBALS['test'][$class::constructed_times]
        );
    }
    
}

class MiddlewareWithClassAndParamDependencies extends Middleware
{
    
    private Foo $foo;
    private Bar $bar;
    
    public function __construct(Foo $foo, Bar $bar, $baz, $biz)
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->baz = $baz;
        $this->biz = $biz;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $request->body = $this->foo->foo.$this->bar->bar.$this->baz.$this->biz;
        
        return $next($request);
    }
    
}

class MiddlewareWithTypedDefault extends Middleware
{
    
    private ?Foo $foo;
    
    public function __construct(?Foo $foo = null)
    {
        $this->foo = $foo;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ( ! is_null($this->foo)) {
            throw new Exception('Foo is not null');
        }
        
        return $next($request);
    }
    
}