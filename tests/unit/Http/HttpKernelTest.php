<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Http;

	use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\HeaderStack;
    use Tests\traits\TestHelpers;
    use Tests\UnitTest;
    use Tests\traits\CreateDefaultWpApiMocks;
	use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\Router;

    class HttpKernelTest extends UnitTest {

        use TestHelpers;
        use CreateDefaultWpApiMocks;

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
		public function no_response_gets_send_when_no_route_matched() {

		    $this->createRoutes(function () {
		        $this->router->get('foo')->handle(function () {
		            return 'foo';
                });
            });

			$request = $this->webRequest( 'GET', '/bar' );

			$this->runAndAssertEmptyOutput($request);
            $this->assertTrue(HeaderStack::isEmpty());


		}

		/** @test */
		public function for_matching_request_headers_and_body_get_send() {

		    $this->createRoutes(function () {

                $this->router->get( '/foo', function () {

                    return 'foo';

                });

		    });

			$request = $this->webRequest( 'GET', '/foo' );

			$this->runAndAssertOutput( 'foo',  $request);
            HeaderStack::assertHas('Content-Type');

		}

		/** @test */
		public function an_event_gets_dispatched_when_a_response_got_send () {

            $this->createRoutes(function () {

                $this->router->get( '/foo', function () {

                    return 'foo';

                });

            });

            $request = $this->webRequest( 'GET', '/foo' );

            $this->runKernel($request);


            $this->expectOutputString('foo');
            ApplicationEvent::assertDispatched(ResponseSent::class);

		}

		/** @test */
		public function when_a_route_matches_null_is_returned_to_WP_and_the_current_template_is_not_included () {

		    $this->createRoutes(function () {

                $this->router->get( '/foo', function () {

                    return 'foo';

                });

		    });

            $this->runAndAssertOutput('foo', $request_event = $this->webRequest('GET', '/foo'));
            $this->assertNull(  $request_event->default() );

		}

        /** @test */
        public function the_kernel_will_return_the_template_WP_tried_to_load_when_no_route_was_found() {

            $this->createRoutes(function () {

                $this->router->get( '/foo', function () {

                    return 'foo';

                });

            });

            $this->runAndAssertEmptyOutput($request_event = $this->webRequest('GET', '/bar'));
            $this->assertSame( 'wordpress.php', $request_event->default() );


        }

        /** @test */
        public function an_invalid_response_returned_from_the_handler_will_lead_to_an_exception () {

            $this->createRoutes(function () {

                $this->router->get( '/foo', function () {

                    return 1;

                });

            });


            $this->expectExceptionMessage('The response returned by the route action is not valid.');

            $this->runKernel($this->webRequest('GET', '/foo'));


        }



	}