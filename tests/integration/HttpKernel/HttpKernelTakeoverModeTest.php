<?php


	declare( strict_types = 1 );


	namespace Tests\integration\HttpKernel;

	use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use BetterWpHooks\ListenerFactory;
	use Codeception\TestCase\WPTestCase;
	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\Middleware\GlobalMiddleware;
	use Tests\stubs\Middleware\WebMiddleware;
	use Tests\stubs\TestErrorHandler;
	use Tests\stubs\TestResponseService;
	use Tests\TestRequest;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Exceptions\InvalidResponseException;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;

	class HttpKernelTakeoverModeTest extends TestCase {

		use SetUpKernel;

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


