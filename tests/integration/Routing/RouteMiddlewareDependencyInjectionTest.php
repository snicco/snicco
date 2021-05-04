<?php


	namespace Tests\integration\Routing;

	use Codeception\TestCase\WPTestCase;
	use Tests\stubs\Middleware\MiddlewareWithDependencies;
	use Tests\TestRequest;

	class RouteMiddlewareDependencyInjectionTest extends WPTestCase {

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

			$this->router->get( '/foo', Admin)

		}

	}