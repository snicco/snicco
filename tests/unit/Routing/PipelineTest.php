<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use Nyholm\Psr7\Response;
    use Nyholm\Psr7\Stream;
    use PHPUnit\Framework\TestCase;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Tests\helpers\CreateContainer;

    use WPEmerge\Routing\Pipeline;

    class PipelineTest extends TestCase
    {

        use CreateContainer;

        /**
         * @var Pipeline
         */
        private $pipeline;

        /**
         * @var ServerRequestInterface
         */
        private $request;

        protected function setUp() : void
        {

            parent::setUp();

            $this->pipeline = new Pipeline($this->createContainer());

            $factory = new Psr17Factory();

            $this->request = $factory->createServerRequest('GET', 'https://foobar.com');

        }

        /** @test */
        public function middleware_can_be_run()
        {

            $response = $this->pipeline
                ->send($this->request)
                ->through([Foo::class])
                ->then(function (ServerRequestInterface $request) {

                    $foo = $request->getAttribute('test');
                    $foo .= 'biz';

                    return new Response(200, [], $foo);

                });

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertSame('foobiz', $response->getBody()->__toString());


        }

        /** @test */
        public function middleware_can_be_stacked()
        {

            /** @var ResponseInterface $response */
            $response = $this->pipeline
                ->send($this->request)
                ->through([Foo::class, Bar::class])
                ->then(function (ServerRequestInterface $request) {

                    $foo = $request->getAttribute('test');
                    $foo .= 'biz';

                    return new Response(200, [], $foo);

                });

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertSame('foobarbiz', $response->getBody()->__toString());

        }

        /** @test */
        public function middleware_can_break_out_of_the_middleware_stack()
        {

            /** @var ResponseInterface $response */
            $response = $this->pipeline
                ->send($this->request)
                ->through([Foo::class, StopMiddleware::class, Bar::class])
                ->then(function (ServerRequestInterface $request) {

                    $this->fail('This should not be called.');

                });

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertSame('fooSTOP', $response->getBody()->__toString());

        }

        /** @test */
        public function middleware_responses_can_be_manipulated_by_middleware_higher_in_the_stack()
        {

            /** @var ResponseInterface $response */
            $response = $this->pipeline
                ->send($this->request)
                ->through([
                    ChangeLastMiddleware::class, Foo::class, StopMiddleware::class, Bar::class,
                ])
                ->then(function (ServerRequestInterface $request) {

                    $this->fail('This should not be called.');

                });

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertSame('CHANGEDfooSTOP', $response->getBody()->__toString());

        }

        /** @test */
        public function middleware_can_be_resolved_from_the_container()
        {

            /** @var ResponseInterface $response */
            $response = $this->pipeline
                ->send($this->request)
                ->through([MiddlewareDependency::class])
                ->then(function (ServerRequestInterface $request) {

                    $this->fail('This should not be called.');

                });

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertSame('BAR', $response->getBody()->__toString());


        }

        /** @test */
        public function middleware_can_receive_config_arguments()
        {

            /** @var ResponseInterface $response */
            $response = $this->pipeline
                ->send($this->request)
                ->through([[MiddlewareWithConfig::class, false]])
                ->then(function (ServerRequestInterface $request) {

                    $this->fail('This should not be called');

                });

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertSame(404, $response->getStatusCode());

            /** @var ResponseInterface $response */
            $response = $this->pipeline
                ->send($this->request)
                ->through([[MiddlewareWithConfig::class, true]])
                ->then(function (ServerRequestInterface $request) {

                    return new Response(200);

                });

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertSame(200, $response->getStatusCode());

        }

        /** @test */
        public function an_anonymous_closure_can_be_middleware()
        {

            /** @var ResponseInterface $response */
            $response = $this->pipeline
                ->send($this->request)
                ->through([

                    function () {

                        return new Response(201);

                    },
                ])
                ->then(function (ServerRequestInterface $request) {

                    $foo = $request->getAttribute('test');
                    $foo .= 'biz';

                    return new Response(200, [], $foo);

                });

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertSame(201, $response->getStatusCode());

        }

        /** @test */
        public function middleware_that_does_not_implement_the_correct_interface_throws_an_exception()
        {

            $this->expectExceptionMessage('Unsupported middleware type:');

            $this->pipeline
                ->send($this->request)
                ->through([WrongMiddleware::class])
                ->then(function () {

                    $this->fail('Invalid middleware did not cause an exception');

                });


        }


    }

    class Foo implements MiddlewareInterface
    {

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
        {

            $test = $request->getAttribute('test', '');

            $response = $handler->handle($request->withAttribute('test', $test .= 'foo'));

            return $response;

        }

    }


    class Bar implements MiddlewareInterface
    {

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
        {

            $test = $request->getAttribute('test', '');

            $response = $handler->handle($request->withAttribute('test', $test .= 'bar'));

            return $response;

        }

    }


    class StopMiddleware implements MiddlewareInterface
    {

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
        {

            $test = $request->getAttribute('test', '');

            return new Response(200, [], $test.'STOP');


        }

    }


    class ChangeLastMiddleware implements MiddlewareInterface
    {

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
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

        public function __construct(\Tests\fixtures\TestDependencies\Bar $bar)
        {

            $this->bar = $bar;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
        {

            return (new Response ())->withBody(Stream::create(strtoupper($this->bar->bar)));


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

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
        {

            if ( ! $this->delegate) {

                return new Response(404);

            }

            return $handler->handle($request);


        }

    }

    class WrongMiddleware {

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
        {

            return new Response();

        }
    }