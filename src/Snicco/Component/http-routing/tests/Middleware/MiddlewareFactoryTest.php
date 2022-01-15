<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use LogicException;
use Test\Helpers\CreateContainer;
use Snicco\Component\Core\DIContainer;
use Tests\Codeception\shared\UnitTest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tests\Codeception\shared\TestDependencies\Bar;
use Tests\Codeception\shared\TestDependencies\Foo;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Middleware\Internal\MiddlewareFactory;
use Snicco\Component\HttpRouting\Tests\fixtures\MiddlewareWithDependencies;

final class MiddlewareFactoryTest extends UnitTest
{
    
    use CreateContainer;
    
    private MiddlewareFactory $factory;
    private DIContainer       $container;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->factory = new MiddlewareFactory($this->container = $this->createContainer());
    }
    
    /** @test */
    public function if_middleware_is_defined_in_the_service_container_it_is_resolved()
    {
        $foo = new Foo();
        $foo->foo = 'FOO_CHANGED';
        $bar = new Bar();
        $bar->bar = 'BAR_CHANGED';
        $this->container->instance(
            MiddlewareWithDependencies::class,
            new MiddlewareWithDependencies($foo, $bar)
        );
        
        $m = $this->factory->create(MiddlewareWithDependencies::class);
        $this->assertInstanceOf(MiddlewareWithDependencies::class, $m);
        $this->assertSame('FOO_CHANGED', $m->foo->foo);
        $this->assertSame('BAR_CHANGED', $m->bar->bar);
    }
    
    /** @test */
    public function if_middleware_without_constructor_args_is_defined_in_the_container_its_resolved()
    {
        $this->container->instance(FooMiddleware::class, $foo_m = new FooMiddleware());
        $this->assertSame($foo_m, $this->factory->create(FooMiddleware::class));
    }
    
    /** @test */
    public function if_route_arguments_are_passed_and_the_middleware_is_in_the_container_its_constructed()
    {
        $foo = new Foo();
        $foo->foo = 'FOO_CHANGED';
        $bar = new Bar();
        $bar->bar = 'BAR_CHANGED';
        $this->container->instance(
            MiddlewareWithDependencies::class,
            new MiddlewareWithDependencies($foo, $bar)
        );
        
        $m = $this->factory->create(MiddlewareWithDependencies::class, ['foo' => 'bar']);
        $this->assertInstanceOf(MiddlewareWithDependencies::class, $m);
        $this->assertSame('FOO_CHANGED', $m->foo->foo);
        $this->assertSame('BAR_CHANGED', $m->bar->bar);
    }
    
    /** @test */
    public function middleware_not_in_the_container_will_newed_up_with_the_passed_args()
    {
        $middleware = $this->factory->create(FooMiddleware::class);
        $this->assertInstanceOf(FooMiddleware::class, $middleware);
        // default
        $this->assertSame('foo_middleware', $middleware->foo);
        
        $middleware = $this->factory->create(FooMiddleware::class, ['FOO_PASSED']);
        $this->assertInstanceOf(FooMiddleware::class, $middleware);
        $this->assertSame('FOO_PASSED', $middleware->foo);
    }
    
    /** @test */
    public function a_middleware_that_needs_both_constructor_arguments_and_runtime_arguments_can_be_returned_as_closure_from_the_container()
    {
        $this->container->singleton(MiddlewareWithContextualAndRuntimeArgs::class, function () {
            return function (string $bar, string $baz) {
                return new MiddlewareWithContextualAndRuntimeArgs(new Foo(), $bar, $baz);
            };
        });
        
        $middleware = $this->factory->create(
            MiddlewareWithContextualAndRuntimeArgs::class,
            ['BAR_PASSED', 'BAZ_PASSED']
        );
        
        $this->assertInstanceOf(MiddlewareWithContextualAndRuntimeArgs::class, $middleware);
        $this->assertSame('BAR_PASSED', $middleware->bar);
        $this->assertSame('BAZ_PASSED', $middleware->baz);
    }
    
    /** @test */
    public function test_exception_if_container_closure_doesnt_return_instance_of_middleware()
    {
        $this->container->singleton(MiddlewareWithContextualAndRuntimeArgs::class, function () {
            return function (string $bar, string $baz) {
                return new Foo();
            };
        });
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Resolving a middleware from the container must return an instance of [%s].\nGot [%s]",
                MiddlewareInterface::class,
                Foo::class
            )
        );
        
        $this->factory->create(
            MiddlewareWithContextualAndRuntimeArgs::class,
            ['BAR_PASSED', 'BAZ_PASSED']
        );
    }
    
}

class MiddlewareWithContextualAndRuntimeArgs extends AbstractMiddleware
{
    
    public string $bar;
    public string $baz;
    private Foo   $foo;
    
    public function __construct(Foo $foo, string $bar, string $baz)
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->baz = $baz;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
    }
    
}