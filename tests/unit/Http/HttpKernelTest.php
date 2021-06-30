<?php


    declare(strict_types = 1);


    namespace Tests\unit\Http;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\fixtures\Middleware\GlobalMiddleware;
    use Tests\fixtures\Middleware\WebMiddleware;
    use Tests\helpers\CreatesWpUrls;
    use Tests\stubs\HeaderStack;
    use Tests\helpers\CreateTestSubjects;
    use Tests\UnitTest;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\ExceptionHandling\Exceptions\NotFoundException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Middleware\Core\EvaluateResponseMiddleware;
    use WPEmerge\Routing\Router;
    use WPEmerge\Session\Session;

    class HttpKernelTest extends UnitTest
    {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;
        use CreatesWpUrls;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

        /**
         * @var AbstractRouteCollection
         */
        private $routes;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);
            HeaderStack::reset();

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
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
            ApplicationEvent::assertDispatched(ResponseSent::class);

        }

        /** @test */
        public function when_a_route_matches_null_is_returned_to_WP_and_the_current_template_is_not_included()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'foo';

                });

            });

            $this->runAndAssertOutput('foo', $request_event = $this->webRequest('GET', '/foo'));
            $this->assertNull($request_event->default());

        }

        /** @test */
        public function the_kernel_will_return_the_template_WP_tried_to_load_when_no_route_was_found()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'foo';

                });

            });

            $this->runAndAssertEmptyOutput($request_event = $this->webRequest('GET', '/bar'));
            $this->assertSame('wordpress.php', $request_event->default());


        }

        /** @test */
        public function an_invalid_response_returned_from_the_handler_will_lead_to_an_exception()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 1;

                });

            });

            $this->expectExceptionMessage('The response returned by the route action is not valid.');

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

            ApplicationEvent::assertDispatched(ResponseSent::class, function ($event) {

                return $event->response instanceof RedirectResponse;

            });
        }

        /** @test */
        public function the_request_is_rebound_in_the_container_after_a_global_routes_run () {

            $this->createRoutes( function () {

                //

            });

            $request = $this->ajaxRequest('test_form');

            $this->assertSame('/wp-admin/admin-ajax.php', $request->routingPath());

            $this->container->instance(Request::class, $request);

            $this->runAndAssertOutput('', new IncomingAjaxRequest($request) );

            /** @var Request $request */
            $request = $this->container->make(Request::class);

            $this->assertSame('/wp-admin/admin-ajax.php/test_form', $request->routingPath());

        }


    }