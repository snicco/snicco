<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Routing;

	use Mockery;
    use Tests\BaseTestCase;
    use Tests\traits\SetUpRouter;
    use Tests\stubs\Controllers\Admin\AdminControllerWithMiddleware;
	use Tests\stubs\Middleware\MiddlewareWithDependencies;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;

    class RouteMiddlewareDependencyInjectionTest extends BaseTestCase {

		use SetUpRouter;


        protected function beforeTestRun()
        {
            $this->newRouter( $c = $this->createContainer() );
            WP::setFacadeContainer($c);
        }

        protected function beforeTearDown()
        {

            Mockery::close();
            WP::clearResolvedInstances();
            WP::setFacadeContainer(null);

        }



        /** @test */
		public function middleware_is_resolved_from_the_service_container () {

			$this->router->get( '/foo', function ( Request $request ) {

				return $request->body;

			})->middleware(MiddlewareWithDependencies::class);

			$request = $this->request( 'GET', '/foo' );
			$this->assertOutput( 'foobar', $this->router->runRoute( $request ) );


		}

		/** @test */
		public function controller_middleware_is_resolved_from_the_service_container () {

			$this->router->get( '/foo', AdminControllerWithMiddleware::class . '@handle');

			$request = $this->request( 'GET', '/foo' );
			$this->assertOutput( 'foobarbaz:controller_with_middleware', $this->router->runRoute( $request ) );

		}

		/** @test */
		public function after_controller_middleware_got_resolved_the_controller_is_not_instantiated_again_when_handling_the_request() {

			$GLOBALS['test'][ AdminControllerWithMiddleware::constructed_times ] = 0;

			$this->router->get( '/foo', AdminControllerWithMiddleware::class . '@handle');

			$request = $this->request( 'GET', '/foo' );
			$this->assertOutput( 'foobarbaz:controller_with_middleware', $this->router->runRoute( $request ) );

			$this->assertRouteActionConstructedTimes(1, AdminControllerWithMiddleware::class);


		}

	}