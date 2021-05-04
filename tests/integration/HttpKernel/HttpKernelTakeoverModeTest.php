<?php


	namespace Tests\integration\HttpKernel;

	use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use BetterWpHooks\ListenerFactory;
	use Codeception\TestCase\WPTestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\Middleware\GlobalMiddleware;
	use Tests\stubs\Middleware\WebMiddleware;
	use Tests\stubs\TestErrorHandler;
	use Tests\stubs\TestResponseService;
	use Tests\TestRequest;
	use WPEmerge\Contracts\Middleware;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\Routing\ConditionFactory;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;

	class HttpKernelTakeoverModeTest extends WPTestCase {

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

			$this->kernel->handle( $this->createIncomingWebRequest( 'GET', 'foo' ) );

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

			$this->kernel->handle( $this->createIncomingWebRequest( 'GET', 'foo' ) );

			$this->assertMiddlewareRunTimes( 1, GlobalMiddleware::class );
			$this->assertMiddlewareRunTimes( 1, WebMiddleware::class );

		}

		/** @test */
		public function web_requests_without_matching_routes_will_return_null_to_the_WP_template_include() {

			$this->router->get( '/foo', function ( TestRequest $request ) {
				//
			});

			$this->kernel->runInTakeoverMode();

			$this->kernel->handle($request_event = $this->createIncomingWebRequest('GET', '/bar'));

			$this->assertNull($request_event->default());

		}


	}


