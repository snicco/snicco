<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Throwable;
use Psr\Log\LogLevel;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7\Response;
use Tests\Core\RoutingTestCase;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Core\Contracts\ExceptionHandler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Core\Http\Psr7\Response as AppResponse;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\BarMiddleware;
use Snicco\Core\Middleware\Internal\MiddlewareFactory;
use Snicco\Core\Middleware\Internal\MiddlewarePipeline;
use Snicco\Core\Middleware\Internal\MiddlewareBlueprint;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\Core\ExceptionHandling\Exceptions\HttpException;
use Snicco\Core\ExceptionHandling\Exceptions\NotFoundException;

class MiddlewarePipelineTest extends RoutingTestCase
{
    
    private MiddlewarePipeline $pipeline;
    private Request            $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->pipeline = new MiddlewarePipeline(
            new MiddlewareFactory($this->container),
            new PipelineTestExceptionHandler(),
        );
        $this->request = new Request(
            $this->psrServerRequestFactory()->createServerRequest('GET', 'https://foobar.com')
        );
    }
    
    /** @test */
    public function a_pipeline_is_immutable()
    {
        $p1 = $this->pipeline->send($this->request);
        
        $this->assertNotSame($p1, $this->pipeline);
        
        $p2 = $this->pipeline->through([]);
        
        $this->assertNotSame($p2, $this->pipeline);
    }
    
    /** @test */
    public function middleware_can_be_run()
    {
        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(Foo::class))
            ->then(function (ServerRequestInterface $request) {
                $foo = $request->getAttribute('test');
                $foo .= 'biz';
                return $this->response_factory->html($foo);
            });
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('foobiz', $response->getBody()->__toString());
    }
    
    /** @test */
    public function middleware_can_be_stacked()
    {
        $response = $this->pipeline
            ->send($this->request)
            ->through(
                array_map([MiddlewareBlueprint::class, 'create'], [Foo::class, Bar::class])
            )
            ->then(function (ServerRequestInterface $request) {
                $foo = $request->getAttribute('test');
                $foo .= 'biz';
                
                return $this->response_factory->html($foo);
            });
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('foobarbiz', $response->getBody()->__toString());
    }
    
    /** @test */
    public function middleware_can_break_out_of_the_middleware_stack()
    {
        $middleware = array_map(
            [MiddlewareBlueprint::class, 'create'],
            [Foo::class, StopMiddleware::class, Bar::class]
        );
        
        $response = $this->pipeline
            ->send($this->request)
            ->through($middleware)
            ->then(function (ServerRequestInterface $request) {
                $this->fail('This should not be called.');
            });
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('fooSTOP', $response->getBody()->__toString());
    }
    
    /** @test */
    public function middleware_responses_can_be_manipulated_by_middleware_higher_in_the_stack()
    {
        $middleware = array_map(
            [MiddlewareBlueprint::class, 'create'],
            [
                ChangeLastMiddleware::class,
                Foo::class,
                StopMiddleware::class,
                Bar::class,
            ]
        );
        
        $response = $this->pipeline
            ->send($this->request)
            ->through($middleware)
            ->then(function (ServerRequestInterface $request) {
                $this->fail('This should not be called.');
            });
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('CHANGEDfooSTOP', $response->getBody()->__toString());
    }
    
    /** @test */
    public function middleware_can_be_resolved_from_the_container()
    {
        $this->container->instance(
            MiddlewareDependency::class,
            new MiddlewareDependency(new \Tests\Codeception\shared\TestDependencies\Bar())
        );
        
        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(MiddlewareDependency::class))
            ->then(function (ServerRequestInterface $request) {
                $this->fail('This should not be called.');
            });
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('BAR', $response->getBody()->__toString());
    }
    
    /** @test */
    public function middleware_can_receive_config_arguments()
    {
        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(MiddlewareWithConfig::class, [false]))
            ->then(function (ServerRequestInterface $request) {
                $this->fail('This should not be called');
            });
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        
        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(MiddlewareWithConfig::class, [true]))
            ->then(function (ServerRequestInterface $request) {
                return $this->response_factory->make();
            });
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }
    
    /** @test */
    public function exceptions_get_handled_on_every_middleware_process_and_dont_break_the_pipeline()
    {
        $middleware = array_map([MiddlewareBlueprint::class, 'create'], [
            FooMiddleware::class,
            ThrowsExceptionMiddleware::class,
            BarMiddleware::class,
        ]);
        
        $response = $this->pipeline
            ->send($this->request)
            ->through($middleware)
            ->then(function (ServerRequestInterface $request) {
                $this->fail(
                    'The route driver should have never be called if we have an exception.'
                );
            });
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        // bar middleware not called
        $this->assertSame('Error Message:foo_middleware', (string) $response->getBody());
    }
    
    /** @test */
    public function exceptions_in_the_request_handler_get_handled_without_breaking_other_middleware()
    {
        $middleware = array_map([MiddlewareBlueprint::class, 'create'], [
            FooMiddleware::class,
            BarMiddleware::class,
        ]);
        
        $response = $this->pipeline
            ->send($this->request)
            ->through($middleware)
            ->then(function (ServerRequestInterface $request) {
                throw new NotFoundException("error");
            });
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('error:bar_middleware:foo_middleware', (string) $response->getBody());
    }
    
    /** @test */
    public function the_same_pipeline_cant_be_run_twice_without_providing_a_new_request()
    {
        $response = $this->pipeline->send($this->request)
                                   ->through([])
                                   ->then(function (Request $request) {
                                       return $this->response_factory->html('foo');
                                   });
        
        $this->assertSame('foo', $response->getBody()->__toString());
        
        $this->expectExceptionMessage(
            'You cant run a middleware pipeline twice without calling send() first.'
        );
        
        $this->pipeline->then(function () { });
    }
    
    /** @test */
    public function test_exception_when_the_pipeline_is_run_without_sending_a_request()
    {
        $this->expectExceptionMessage(
            'You cant run a middleware pipeline twice without calling send() first.'
        );
        
        $this->pipeline->then(function () { });
    }
    
    /** @test */
    public function middleware_is_replaced_and_not_merged_when_using_the_same_pipeline_twice()
    {
        $response = $this->pipeline
            ->send($this->request)
            ->through(MiddlewareBlueprint::create(Foo::class))
            ->then(function (ServerRequestInterface $request) {
                $foo = $request->getAttribute('test');
                $foo .= 'biz';
                
                return $this->response_factory->html($foo);
            });
        
        $this->assertSame('foobiz', $response->getBody()->__toString());
        
        $response = $this->pipeline
            ->send($this->request->withHeader('X-Test', 'foo'))
            ->through(MiddlewareBlueprint::create(Bar::class))
            ->then(function (ServerRequestInterface $request) {
                $foo = $request->getAttribute('test');
                $foo .= 'biz';
                
                return $this->response_factory->html($foo);
            });
        
        $this->assertSame('barbiz', $response->getBody()->__toString());
    }
    
}

class PipelineTestExceptionHandler implements ExceptionHandler
{
    
    use CreatePsr17Factories;
    
    public function toHttpResponse(Throwable $e, Request $request) :AppResponse
    {
        $code = $e instanceof HttpException ? $e->httpStatusCode() : 500;
        $body = $e instanceof HttpException ? $e->getMessage() : 'Internal Server Error';
        $body = $this->psrStreamFactory()->createStream($body);
        
        return new AppResponse(
            $this->psrResponseFactory()->createResponse((int) $code)
                 ->withBody($body)
        );
    }
    
    public function report(Throwable $e, Request $request, string $psr3_log_level = LogLevel::ERROR)
    {
    }
    
}

class Foo implements MiddlewareInterface
{
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        $test = $request->getAttribute('test', '');
        
        $response = $handler->handle($request->withAttribute('test', $test .= 'foo'));
        
        return $response;
    }
    
}

class Bar implements MiddlewareInterface
{
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        $test = $request->getAttribute('test', '');
        
        $response = $handler->handle($request->withAttribute('test', $test .= 'bar'));
        
        return $response;
    }
    
}

class ThrowsExceptionMiddleware implements MiddlewareInterface
{
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        throw new HttpException(404, 'Error Message');
    }
    
}

class StopMiddleware implements MiddlewareInterface
{
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        $test = $request->getAttribute('test', '');
        
        return new AppResponse(new Response(200, [], $test.'STOP'));
    }
    
}

class ChangeLastMiddleware implements MiddlewareInterface
{
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        $response = $handler->handle($request);
        
        $value = $response->getBody()->__toString();
        
        return $response->withBody(Stream::create('CHANGED'.$value));
    }
    
}

class MiddlewareDependency implements MiddlewareInterface
{
    
    /**
     * @var Bar
     */
    private $bar;
    
    public function __construct(\Tests\Codeception\shared\TestDependencies\Bar $bar)
    {
        $this->bar = $bar;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        return (new AppResponse(new Response ()))->withBody(
            Stream::create(strtoupper($this->bar->bar))
        );
    }
    
}

class MiddlewareWithConfig implements MiddlewareInterface
{
    
    /**
     * @var bool
     */
    private $delegate = false;
    
    public function __construct(bool $delegate)
    {
        $this->delegate = $delegate;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        if ( ! $this->delegate) {
            return new AppResponse(new Response(404));
        }
        
        return $handler->handle($request);
    }
    
}

class WrongMiddleware
{
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        return new AppResponse(new Response());
    }
    
}