<?php


    declare(strict_types = 1);


    namespace Tests\unit\Http;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Snicco\Contracts\AbstractRouteCollection;
    use Snicco\Events\Event;
    use Snicco\Events\IncomingAjaxRequest;
    use Snicco\Events\ResponseSent;
    use Snicco\ExceptionHandling\Exceptions\NotFoundException;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\ResponseFactory;
    use Snicco\Http\Responses\RedirectResponse;
    use Snicco\Middleware\Core\EvaluateResponseMiddleware;
    use Snicco\Routing\Router;
    use Snicco\Support\WP;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreatesWpUrls;
    use Tests\helpers\CreateTestSubjects;
    use Tests\stubs\HeaderStack;
    use Tests\UnitTest;

    class HttpKernelTest extends UnitTest
    {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;
        use CreatesWpUrls;

        private ContainerAdapter $container;

        private Router $router;

        private AbstractRouteCollection $routes;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            Event::make($this->container);
            Event::fake();
            WP::setFacadeContainer($this->container);
            HeaderStack::reset();

        }

        protected function beforeTearDown()
        {

            Event::setInstance(null);
            Mockery::close();
            WP::reset();
            HeaderStack::reset();


        }

        /** @test */
        public function no_response_gets_send_when_no_route_matched()
        {

            $this->createRoutes(function () {

                $this->router->get('foo')->handle(function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('GET', '/bar');

            $this->runAndAssertEmptyOutput($request);
            $this->assertTrue(HeaderStack::isEmpty());


        }

        /** @test */
        public function for_matching_request_headers_and_body_get_send()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('GET', '/foo');

            $this->runAndAssertOutput('foo', $request);
            HeaderStack::assertHas('Content-Type');

        }

        /** @test */
        public function an_event_gets_dispatched_when_a_response_got_send()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest('GET', '/foo');

            $this->runKernel($request);

            $this->expectOutputString('foo');
            Event::assertDispatched(ResponseSent::class);

        }

        /** @test */
        public function an_invalid_response_returned_from_the_handler_will_lead_to_an_exception()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 1;

                });

            });

            $this->expectExceptionMessage('The response returned by the route is not valid.');

            $this->runKernel($this->webRequest('GET', '/foo'));


        }

        /** @test */
        public function an_exception_is_thrown_when_the_kernel_must_match_web_routes_and_no_route_matched()
        {

            $this->createRoutes(function () {

                $this->router->get('/bar', function () {

                    return 'bar';

                });

            });

            $this->container->singleton(EvaluateResponseMiddleware::class, function () {

                return new EvaluateResponseMiddleware(true);
            }
            );

            $this->expectException(NotFoundException::class);

            $this->runKernel($this->webRequest('GET', '/foo'));

        }

        /** @test */
        public function a_redirect_response_will_shut_down_the_script_by_dispatching_an_event()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function (ResponseFactory $factory) {

                    return $factory->redirect()->to('bar');

                });

            });

            $this->runKernel($this->webRequest('GET', '/foo'));

            Event::assertDispatched(ResponseSent::class, function ($event) {

                return $event->response instanceof RedirectResponse;

            });
        }

        /** @test */
        public function the_request_is_rebound_in_the_container_after_a_global_routes_run()
        {

            $this->createRoutes(function () {

                //

            });

            $request = $this->ajaxRequest('test_form');

            $this->assertSame('/wp-admin/admin-ajax.php', $request->routingPath());

            $this->container->instance(Request::class, $request);

            $this->runAndAssertOutput('', new IncomingAjaxRequest($request));

            /** @var Request $request */
            $request = $this->container->make(Request::class);

            $this->assertSame('/wp-admin/admin-ajax.php/test_form', $request->routingPath());

        }


    }