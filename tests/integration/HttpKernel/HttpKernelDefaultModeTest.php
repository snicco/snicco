<?php


	declare( strict_types = 1 );


	namespace Tests\integration\HttpKernel;

	use PHPUnit\Framework\TestCase;
	use Tests\stubs\Middleware\GlobalMiddleware;
	use Tests\stubs\Middleware\WebMiddleware;


	class HttpKernelDefaultModeTest extends TestCase {

		use SetUpKernel;

		/** @test */
		public function the_kernel_does_not_run_global_middleware_when_not_matching_a_route() {

			$GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;

			$request = $this->createIncomingWebRequest( 'GET', 'foo' );

			$this->kernel->setMiddlewareGroups( [

				'global' => [ GlobalMiddleware::class ],

			] );

			$this->assertSame('', $this->runAndGetKernelOutput($request));

			$this->assertMiddlewareRunTimes(0, GlobalMiddleware::class);

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

			$this->assertSame('foo', $output);
			$this->assertMiddlewareRunTimes(1 , WebMiddleware::class);

		}

		/** @test */
		public function global_middleware_is_only_run_by_the_router_when_a_route_matched_and_not_by_the_kernel() {

			$GLOBALS['test'][ GlobalMiddleware::run_times ] = 0;

			$this->kernel->setMiddlewareGroups( [
					'global' => [ GlobalMiddleware::class]
				] );
			$this->router->get( '/foo', function () {

				return 'foo';

			} );

			// non matching request
			$request = $this->createIncomingWebRequest( 'POST', '/foo' );
			$this->assertSame('', $this->runAndGetKernelOutput($request));
			$this->assertMiddlewareRunTimes(0 , GlobalMiddleware::class);

			// matching request
			$request = $this->createIncomingWebRequest( 'GET', '/foo' );
			$this->assertSame('foo', $this->runAndGetKernelOutput($request));
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


