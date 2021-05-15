<?php


	declare( strict_types = 1 );


	namespace Tests\integration\HttpKernel;

	use Mockery;
    use Tests\BaseTestCase;
    use Tests\CreateDefaultWpApiMocks;
    use Tests\stubs\Middleware\GlobalMiddleware;
    use Tests\stubs\Middleware\WebMiddleware;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Events\HeadersSent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;

	class HttpKernelTest extends BaseTestCase {

		use SetUpKernel;
        use CreateDefaultWpApiMocks;


        protected function beforeTestRun()
        {
            $this->router = $this->newRouter($c = $this->createContainer());
            $this->kernel = $this->newKernel($this->router, $c );
            ApplicationEvent::make($c);
            ApplicationEvent::fake();
            WP::setFacadeContainer($c);

        }

        protected function beforeTearDown()
        {
            ApplicationEvent::setInstance(null);
            Wp::setFacadeContainer(null);
            Wp::clearResolvedInstances();
            Mockery::close();

        }


		/** @test */
		public function no_response_gets_send_when_no_route_matched() {

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );

			$output = $this->runAndGetKernelOutput($request);

			$this->assertNothingSent($output);

		}

		/** @test */
		public function for_matching_request_headers_and_body_get_send() {


			$this->router->get( '/foo', function ( Request $request ) {

				return 'foo';

			});

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );

			$this->assertBodySent('foo', $this->runAndGetKernelOutput($request));

		}

		/** @test */
		public function for_admin_requests_the_body_does_not_get_send_immediately () {


			$this->router->get( '/admin', function () {

				return 'foo';

			});

			$request = $this->createIncomingAdminRequest( 'GET', '/admin' );

			$this->assertNothingSent($this->runAndGetKernelOutput($request));

			ob_start();
			$this->kernel->sendBodyDeferred();
			$body = ob_get_clean();

			$this->assertBodySent('foo', $body);

		}

		/** @test */
		public function events_are_dispatched_when_a_headers_and_body_get_send () {

			$this->router->get( '/foo', function ( ) {

				return 'foo';

			} );

			$request = $this->createIncomingWebRequest( 'GET', '/foo' );
			$this->runAndGetKernelOutput($request);

			ApplicationEvent::assertDispatched(HeadersSent::class , function ($event) use ( $request ) {

				return $event->request = $request;


			});
			ApplicationEvent::assertDispatched(BodySent::class , function ($event) use ( $request ) {

				return $event->request = $request;


			});


		}

		/** @test */
		public function the_body_will_never_be_sent_when_the_kernel_did_not_receive_a_response_for_admin_requests() {

			ob_start();
			$this->kernel->sendBodyDeferred();

			$this->assertNothingSent(ob_get_clean());

		}

		/** @test */
		public function when_a_route_matches_null_is_returned_to_WP_and_the_current_template_is_not_included () {

            $this->router->get( '/foo', function (  ) {

                return 'foo';

            } );


            $output = $this->runAndGetKernelOutput(

                $request_event = $this->createIncomingWebRequest( 'GET', '/foo' )

            );

            $this->assertOutput('foo', $output);
            $this->assertNull(  $request_event->default() );

		}

        /** @test */
        public function the_kernel_will_return_the_template_WP_tried_to_load_when_no_route_was_found() {

            $this->router->get( '/foo', function (  ) {

                //

            } );


            $output = $this->runAndGetKernelOutput(

                $request_event = $this->createIncomingWebRequest( 'GET', '/bar' )

            );

            $this->assertSame('', $output);
            $this->assertSame( 'wordpress.php', $request_event->default() );

        }

        /** @test */
        public function the_kernel_does_not_run_global_middleware_when_not_matching_a_route() {

            $GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;

            $request = $this->createIncomingWebRequest( 'GET', 'foo' );

            $this->kernel->setMiddlewareGroups( [

                'global' => [ GlobalMiddleware::class ],

            ] );

            $this->assertOutput('', $this->runAndGetKernelOutput($request) );

            $this->assertMiddlewareRunTimes(0, GlobalMiddleware::class);

        }

        /** @test */
        public function middleware_is_synced_to_the_router_and_run_before_a_matching_route() {

            $GLOBALS['test'][ WebMiddleware::run_times ] = 0;

            $this->kernel->setRouteMiddlewareAliases( [

                'web' => WebMiddleware::class,

            ] );
            $this->router->get( '/foo', function () {

                return 'foo';

            } )->middleware( 'web' );

            $request = $this->createIncomingWebRequest( 'GET', '/foo' );

            $output = $this->runAndGetKernelOutput($request);

            $this->assertOutput('foo', $output);
            $this->assertMiddlewareRunTimes(1 , WebMiddleware::class);

        }

        /** @test */
        public function global_middleware_is_only_run_by_the_router_when_a_route_matched_and_not_by_the_kernel() {

            $GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;

            $this->kernel->setMiddlewareGroups([
                'global' => [ GlobalMiddleware::class]
            ]);
            $this->router->get( '/foo', function () {

                return 'foo';

            } );

            // non matching request
            $request = $this->createIncomingWebRequest( 'POST', '/foo' );
            $this->assertNothingSent($this->runAndGetKernelOutput($request));
            $this->assertMiddlewareRunTimes(0 , GlobalMiddleware::class);

            // matching request
            $request = $this->createIncomingWebRequest( 'GET', '/foo' );
            $this->assertOutput('foo', $this->runAndGetKernelOutput($request));
            $this->assertMiddlewareRunTimes(1 , GlobalMiddleware::class);

        }

        /** @test */
        public function global_middleware_can_be_disabled_for_testing_purposes () {

            $GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;

            $this->kernel->setMiddlewareGroups( [
                'global' => [ GlobalMiddleware::class]
            ] );
            $this->router->get( '/foo', function () {

                return 'foo';

            } );

            $this->kernel->runInTestMode();
            $request = $this->createIncomingWebRequest( 'GET', '/foo' );


            $this->assertSame('foo', $this->runAndGetKernelOutput($request));
            $this->assertMiddlewareRunTimes(0 , GlobalMiddleware::class);

        }


	}