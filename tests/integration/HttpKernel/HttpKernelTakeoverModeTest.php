<?php


	declare( strict_types = 1 );


	namespace Tests\integration\HttpKernel;

	use Mockery;
    use Tests\BaseTestCase;
    use Tests\CreateDefaultWpApiMocks;
    use Tests\stubs\Middleware\GlobalMiddleware;
	use Tests\stubs\Middleware\WebMiddleware;
	use Tests\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Exceptions\InvalidResponseException;
    use WPEmerge\Facade\WP;

    class HttpKernelTakeoverModeTest extends BaseTestCase {

	    use CreateDefaultWpApiMocks;
		use SetUpKernel;

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
		public function the_kernel_will_always_run_global_middleware_even_when_not_matching_a_request() {

			$GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;
			$GLOBALS['test'][ WebMiddleware::run_times ]    = 0;

			$this->kernel->runInTakeoverMode();
			$this->kernel->setMiddlewareGroups( [

				'global' => [ GlobalMiddleware::class ],
				'web'    => [ WebMiddleware::class ],

			] );

			$request_event = $this->createIncomingWebRequest( 'GET', 'foo' );

			try {

				ob_start();
				$this->kernel->handle($request_event);
				$this->fail('Exception was expected for a non matching route');

			}

			catch ( InvalidResponseException $e ) {

				$output = ob_get_clean();
				$this->assertSame('', $output);

			}

			$this->assertMiddlewareRunTimes( 1, GlobalMiddleware::class );
			$this->assertMiddlewareRunTimes( 0, WebMiddleware::class );


		}

		/** @test */
		public function for_matching_requests_global_middleware_will_not_be_run_again_by_the_router() {

			$GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;
			$GLOBALS['test'][ WebMiddleware::run_times ]    = 0;

			$this->kernel->runInTakeoverMode();
			$this->kernel->setMiddlewareGroups( [

				'global' => [ GlobalMiddleware::class ],
				'web'    => [ WebMiddleware::class ],

			] );

			$this->router->get( '/foo', function () {
				//
			} )->middleware( 'web' );


			try {

				ob_start();
				$this->kernel->handle( $this->createIncomingWebRequest( 'GET', 'foo' ) );
				$this->fail('Exception was expected for a non matching route');

			}

			catch ( InvalidResponseException $e ) {

				$output = ob_get_clean();
				$this->assertSame('', $output);

			}


			$this->assertMiddlewareRunTimes( 1, GlobalMiddleware::class );
			$this->assertMiddlewareRunTimes( 1, WebMiddleware::class );

		}

		/** @test */
		public function web_requests_without_matching_routes_will_return_null_to_the_WP_template_include() {

			$this->router->get( '/foo', function ( TestRequest $request ) {
				//
			});

			$this->kernel->runInTakeoverMode();

			$request_event = $this->createIncomingWebRequest('GET', '/bar');

			try {

				ob_start();
				$this->kernel->handle($request_event);
				$this->fail('Exception was expected for a non matching route');

			}

			catch (InvalidResponseException $e) {

				$output = ob_get_clean();
				$this->assertSame('', $output);

			}

			$this->assertNull($request_event->default());



		}

		/** @test */
		public function an_invalid_or_null_response_returned_from_the_handler_will_lead_to_an_exception () {

			$this->router->get( '/foo', function ( TestRequest $request ) {
				//
			});

			$this->kernel->runInTakeoverMode();

			$this->expectExceptionMessage('The response by the route action is not valid');

			$this->kernel->handle($this->createIncomingWebRequest('GET', '/bar'));



		}

	}


