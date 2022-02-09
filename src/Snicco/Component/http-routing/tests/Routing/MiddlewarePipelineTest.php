<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\AbstractMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\LazyHttpErrorHandler;
use Snicco\Component\HttpRouting\MiddlewareBlueprint;
use Snicco\Component\HttpRouting\MiddlewarePipeline;
use Snicco\Component\HttpRouting\NextMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\MiddlewareWithDependencies;
use Snicco\Component\HttpRouting\Tests\fixtures\NullErrorHandler;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Bar;
use Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies\Foo;
use Snicco\Component\HttpRouting\Tests\helpers\CreateHttpErrorHandler;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsrContainer;
use Snicco\Component\HttpRouting\Tests\helpers\CreateUrlGenerator;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;

use function array_map;

class MiddlewarePipelineTest extends TestCase
{

    use CreateTestPsrContainer;
    use CreateTestPsr17Factories;
    use CreateHttpErrorHandler;
    use CreateUrlGenerator;

    private MiddlewarePipeline $pipeline;
    private Request $request;
    private ResponseFactory $response_factory;
    private DIContainer $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createContainer();
        $this->container[HttpErrorHandlerInterface::class] = $this->createHttpErrorHandler(
            $this->response_factory = $this->createResponseFactory($this->createUrlGenerator())
        );
        $this->container[ResponseFactory::class] = $this->response_factory;

        $this->pipeline = new MiddlewarePipeline(
            $this->container,
            new NullErrorHandler(),
        );
        $this->request = new Request(
            $this->psrServerRequestFactory()->createServerRequest('GET', 'https://foobar.com')
        );
    }

    /**
     * @test
     */
    public function a_pipeline_is_immutable(): void
    {
        $p1 = $this->pipeline->send($this->request);

        $this->assertNotSame($p1, $this->pipeline);

        $p2 = $this->pipeline->through([]);

        $this->assertNotSame($p2, $this->pipeline);
    }

    /**
     * @test
     */
    public function middleware_can_be_run(): void
    {
        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(PipelineTestMiddleware1::class))
            ->then(function (ServerRequestInterface $request) {
                return $this->response_factory->html(
                    (string)$request->getAttribute(PipelineTestMiddleware1::ATTRIBUTE)
                );
            });

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('foo:pm1', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function instantiated_middleware_can_be_used(): void
    {
        $foo = new FooMiddleware('FOO');

        $response = $this->pipeline
            ->send($this->request)
            ->through([
                    $foo,
                    MiddlewareBlueprint::create(BarMiddleware::class, ['BAR']),
                ]
            )
            ->then(function () {
                return $this->response_factory->html('handler');
            });

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('handler:BAR:FOO', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function middleware_can_be_stacked(): void
    {
        $response = $this->pipeline
            ->send($this->request)
            ->through(
                array_map(
                    [MiddlewareBlueprint::class, 'create'],
                    [PipelineTestMiddleware1::class, PipelineTestMiddleware2::class]
                )
            )
            ->then(function (ServerRequestInterface $request) {
                return $this->response_factory->html(
                    (string)$request->getAttribute(PipelineTestMiddleware1::ATTRIBUTE) .
                    (string)$request->getAttribute(PipelineTestMiddleware2::ATTRIBUTE)
                );
            });

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('foobar:pm2:pm1', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function middleware_can_break_out_of_the_middleware_stack(): void
    {
        $middleware = array_map(
            [MiddlewareBlueprint::class, 'create'],
            [PipelineTestMiddleware1::class, StopMiddleware::class, PipelineTestMiddleware2::class]
        );

        $response = $this->pipeline
            ->send($this->request)
            ->through($middleware)
            ->then(function () {
                throw new RuntimeException('This should never run.');
            });

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('stopped:pm1', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function middleware_can_be_resolved_from_the_container(): void
    {
        $this->container->instance(
            MiddlewareWithDependencies::class,
            new MiddlewareWithDependencies(new Foo('FOO'), new Bar('BAR'))
        );

        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(MiddlewareWithDependencies::class))
            ->then(function () {
                return $this->response_factory->html('handler');
            });

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('handler:FOOBAR', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function middleware_can_receive_config_arguments(): void
    {
        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(FooMiddleware::class, ['FOO_M']))
            ->then(function () {
                return $this->response_factory->html('foo_handler');
            });

        $this->assertSame('foo_handler:FOO_M', (string)$response->getBody());

        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(FooMiddleware::class, ['FOO_M_DIFFERENT']))
            ->then(function () {
                return $this->response_factory->html('foo_handler');
            });

        $this->assertSame('foo_handler:FOO_M_DIFFERENT', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function exceptions_get_handled_on_every_middleware_process_and_dont_break_the_pipeline(): void
    {
        $pipeline = new MiddlewarePipeline(
            $this->container,
            new LazyHttpErrorHandler($this->container)
        );

        $middleware = array_map([MiddlewareBlueprint::class, 'create'], [
            FooMiddleware::class,
            ThrowExceptionMiddleware::class,
            BarMiddleware::class,
        ]);

        $response = $pipeline
            ->send($this->request)
            ->through($middleware)
            ->then(function () {
                throw new RuntimeException('This should never run.');
            });

        $body = (string)$response->getBody();

        $this->assertStringStartsWith('<h1>Oops! An Error Occurred</h1>', $body);
        $this->assertStringEndsWith('foo_middleware', $body);
    }

    /**
     * @test
     */
    public function exceptions_in_the_request_handler_get_handled_without_breaking_other_middleware(): void
    {
        $pipeline = new MiddlewarePipeline(
            $this->container,
            new LazyHttpErrorHandler($this->container)
        );

        $middleware = array_map([MiddlewareBlueprint::class, 'create'], [
            FooMiddleware::class,
            BarMiddleware::class,
        ]);

        $response = $pipeline
            ->send($this->request)
            ->through($middleware)
            ->then(function () {
                throw new RuntimeException('error');
            });

        $body = (string)$response->getBody();

        $this->assertStringStartsWith('<h1>Oops! An Error Occurred</h1>', $body);
        $this->assertStringEndsWith(':bar_middleware:foo_middleware', $body);
    }

    /**
     * @test
     */
    public function the_same_pipeline_cant_be_run_twice_without_providing_a_new_request(): void
    {
        $response = $this->pipeline->send($this->request)
            ->through([])
            ->then(function () {
                return $this->response_factory->html('foo');
            });

        $this->assertSame('foo', $response->getBody()->__toString());

        $this->expectExceptionMessage(
            'You cant run a middleware pipeline twice without calling send() first.'
        );

        $this->pipeline->then(function () {
            return $this->response_factory->make();
        });
    }

    /**
     * @test
     */
    public function test_exception_when_the_pipeline_is_run_without_sending_a_request(): void
    {
        $this->expectExceptionMessage(
            'You cant run a middleware pipeline twice without calling send() first.'
        );

        $this->pipeline->then(function () {
            return $this->response_factory->html('foo');
        });
    }

    /**
     * @test
     */
    public function middleware_is_replaced_and_not_merged_when_using_the_same_pipeline_twice(): void
    {
        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(FooMiddleware::class))
            ->then(function () {
                return $this->response_factory->html('foo');
            });

        $this->assertSame('foo:foo_middleware', $response->getBody()->__toString());

        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(BarMiddleware::class))
            ->then(function () {
                return $this->response_factory->html('foo');
            });

        $this->assertSame('foo:bar_middleware', $response->getBody()->__toString());
    }

}

class ThrowExceptionMiddleware extends AbstractMiddleware
{

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        throw new RuntimeException('Error in middleware');
    }

}

class StopMiddleware extends AbstractMiddleware
{

    const ATTR = 'stop_middleware';

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        return $this->respond()->html('stopped');
    }

}

class PipelineTestMiddleware1 extends AbstractMiddleware
{

    const ATTRIBUTE = 'pipeline1';
    private string $value_to_add;

    public function __construct(string $value_to_add = 'foo')
    {
        $this->value_to_add = $value_to_add;
    }

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request->withAttribute(self::ATTRIBUTE, $this->value_to_add));
        $response->getBody()->write(':pm1');
        return $response;
    }

}

class PipelineTestMiddleware2 extends AbstractMiddleware
{

    const ATTRIBUTE = 'pipeline2';
    private string $value_to_add;

    public function __construct(string $value_to_add = 'bar')
    {
        $this->value_to_add = $value_to_add;
    }

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request->withAttribute(self::ATTRIBUTE, $this->value_to_add));
        $response->getBody()->write(':pm2');
        return $response;
    }

}
