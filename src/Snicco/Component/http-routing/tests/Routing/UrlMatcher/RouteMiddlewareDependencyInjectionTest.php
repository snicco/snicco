<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\ControllerWithMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\MiddlewareWithDependencies;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Bar;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Baz;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Foo;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

use const SEEK_END;

/**
 * @internal
 */
final class RouteMiddlewareDependencyInjectionTest extends HttpRunnerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->pimple[MiddlewareWithDependencies::class] = fn (): MiddlewareWithDependencies => new MiddlewareWithDependencies(
            new Foo(),
            new Bar()
        );

        $this->pimple[ControllerWithMiddleware::class] = fn (): ControllerWithMiddleware => new ControllerWithMiddleware(
            new Baz()
        );
    }

    /**
     * @test
     */
    public function middleware_is_resolved_from_the_service_container(): void
    {
        $foo = new Foo();
        $foo->value = 'FOO';

        $bar = new Bar();
        $bar->value = 'BAR';

        $this->pimple[MiddlewareWithDependencies::class] = fn (): MiddlewareWithDependencies => new MiddlewareWithDependencies(
            $foo,
            $bar
        );

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware(
                MiddlewareWithDependencies::class
            );
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':FOOBAR', $request);
    }

    /**
     * @test
     */
    public function controller_middleware_is_resolved_from_the_service_container(): void
    {
        $this->pimple[ControllerWithMiddleware::class] = function (): ControllerWithMiddleware {
            $baz = new Baz();
            $baz->value = 'BAZ';

            return new ControllerWithMiddleware($baz);
        };

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', ControllerWithMiddleware::class . '@handle');
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('BAZ:controller_with_middleware:foobar', $request);
    }

    /**
     * @test
     * @psalm-suppress MixedArgument
     */
    public function middleware_arguments_are_passed_after_any_class_dependencies(): void
    {
        $foo = new Foo();
        $foo->value = 'FOO';

        $bar = new Bar();
        $bar->value = 'BAR';

        $this->pimple[Foo::class] = fn (): Foo => $foo;
        $this->pimple[Bar::class] = fn (): Bar => $bar;

        $this->pimple[MiddlewareWithClassAndParamDependencies::class] = $this->pimple->protect(
            fn (string $foo, string $bar): \Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher\MiddlewareWithClassAndParamDependencies => new MiddlewareWithClassAndParamDependencies(
                $this->pimple[Foo::class],
                $this->pimple[Bar::class],
                $foo,
                $bar
            )
        );

        $this->withMiddlewareAlias([
            'm' => MiddlewareWithClassAndParamDependencies::class,
        ]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware('m:BAZ,BIZ');
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':FOOBARBAZBIZ', $request);
    }

    /**
     * @test
     */
    public function a_middleware_with_a_typed_default_value_and_no_passed_arguments_works(): void
    {
        $this->withMiddlewareAlias([
            'm' => MiddlewareWithTypedDefault::class,
        ]);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo', RoutingTestController::class)->middleware('m');
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
}

final class MiddlewareWithClassAndParamDependencies extends Middleware
{
    private Foo $foo;

    private Bar $bar;

    private string $baz;

    private string $biz;

    public function __construct(Foo $foo, Bar $bar, string $baz, string $biz)
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->baz = $baz;
        $this->biz = $biz;
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        $body = $response->getBody();

        $body->seek(0, SEEK_END);

        $body->write(':' . $this->foo->value . $this->bar->value . $this->baz . $this->biz);

        return $response;
    }
}

final class MiddlewareWithTypedDefault extends Middleware
{
    private ?Foo $foo;

    public function __construct(?Foo $foo = null)
    {
        $this->foo = $foo;
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        if (null !== $this->foo) {
            throw new RuntimeException('Foo is not null');
        }

        return $next($request);
    }
}
