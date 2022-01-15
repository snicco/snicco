<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Codeception\shared\TestDependencies\Bar;
use Tests\Codeception\shared\TestDependencies\Baz;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Tests\RoutingTestCase;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\MiddlewareWithDependencies;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\ControllerWithMiddleware;

class RouteMiddlewareDependencyInjectionTest extends RoutingTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->container->instance(
            MiddlewareWithDependencies::class,
            new MiddlewareWithDependencies(new Foo(), new Bar())
        );
        
        $this->container->singleton(ControllerWithMiddleware::class, function () {
            return new ControllerWithMiddleware(new Baz());
        });
    }
    
    /** @test */
    public function middleware_is_resolved_from_the_service_container()
    {
        $foo = new Foo();
        $foo->foo = 'FOO';
        
        $bar = new Bar();
        $bar->bar = 'BAR';
        
        $this->container->instance(
            MiddlewareWithDependencies::class,
            new MiddlewareWithDependencies($foo, $bar)
        );
        
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            MiddlewareWithDependencies::class
        );
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':FOOBAR', $request);
    }
    
    /** @test */
    public function controller_middleware_is_resolved_from_the_service_container()
    {
        $this->container->singleton(ControllerWithMiddleware::class, function () {
            $baz = new Baz();
            $baz->baz = 'BAZ';
            return new ControllerWithMiddleware($baz);
        });
        
        $this->routeConfigurator()->get('r1', '/foo', ControllerWithMiddleware::class.'@handle');
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody('BAZ:controller_with_middleware:foobar', $request);
    }
    
    /** @test */
    public function after_controller_middleware_got_resolved_the_controller_is_not_instantiated_again_when_handling_the_request()
    {
        $GLOBALS['test'][ControllerWithMiddleware::CONSTRUCTED_KEY] = 0;
        
        $this->routeConfigurator()->get('r1', '/foo', ControllerWithMiddleware::class.'@handle');
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody('baz:controller_with_middleware:foobar', $request);
        
        $this->assertSame(1, $GLOBALS['test'][ControllerWithMiddleware::CONSTRUCTED_KEY] ?? 0);
    }
    
    /** @test */
    public function middleware_arguments_are_passed_after_any_class_dependencies()
    {
        $foo = new Foo();
        $foo->foo = 'FOO';
        
        $bar = new Bar();
        $bar->bar = 'BAR';
        
        $this->container->instance(Foo::class, $foo);
        $this->container->instance(Bar::class, $bar);
        
        $this->container->instance(
            MiddlewareWithClassAndParamDependencies::class,
            function ($foo, $bar) {
                return new MiddlewareWithClassAndParamDependencies(
                    $this->container[Foo::class],
                    $this->container[Bar::class],
                    $foo,
                    $bar
                );
            }
        );
        
        $this->withMiddlewareAlias([
            'm' => MiddlewareWithClassAndParamDependencies::class,
        ]);
        
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            'm:BAZ,BIZ'
        );
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':FOOBARBAZBIZ', $request);
    }
    
    /** @test */
    public function a_middleware_with_a_typed_default_value_and_no_passed_arguments_works()
    {
        $this->withMiddlewareAlias([
            'm' => MiddlewareWithTypedDefault::class,
        ]);
        
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            'm'
        );
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    private function assertRouteActionConstructedTimes(int $times, $class)
    {
    }
    
}

class MiddlewareWithClassAndParamDependencies extends AbstractMiddleware
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
        $response = $next($request);
        
        $response->getBody()->write(':'.$this->foo->foo.$this->bar->bar.$this->baz.$this->biz);
        return $response;
    }
    
}

class MiddlewareWithTypedDefault extends AbstractMiddleware
{
    
    private ?Foo $foo;
    
    public function __construct(?Foo $foo = null)
    {
        $this->foo = $foo;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ( ! is_null($this->foo)) {
            throw new RuntimeException('Foo is not null');
        }
        
        return $next($request);
    }
    
}