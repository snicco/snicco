<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use PHPUnit\Framework\TestCase;
	use Tests\stubs\Controllers\Admin\AdminControllerWithMiddleware;
	use Tests\stubs\Middleware\MiddlewareWithDependencies;
	use Tests\TestRequest;

	class RouteMiddlewareDependencyInjectionTest extends TestCase {

		use SetUpRouter;

		/** @test */
		public function middleware_is_resolved_from_the_service_container () {

			$this->router->get( '/foo', function ( TestRequest $request ) {

				return $request->body;

			})->middleware(MiddlewareWithDependencies::class);

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foobar', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function controller_middleware_is_resolved_from_the_service_container () {

			$this->router->get( '/foo', AdminControllerWithMiddleware::class . '@handle');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foobarbaz:controller_with_middleware', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function after_controller_middleware_got_resolved_the_controller_is_not_instantiated_again_when_handling_the_request() {

			$GLOBALS['test'][ AdminControllerWithMiddleware::constructed_times ] = 0;

			$this->router->get( '/foo', AdminControllerWithMiddleware::class . '@handle');

			$request = $this->request( 'GET', '/foo' );
			$this->seeResponse( 'foobarbaz:controller_with_middleware', $this->router->runRoute( $request ) );

			$this->assertRouteActionConstructedTimes(1, AdminControllerWithMiddleware::class);



		}

	}