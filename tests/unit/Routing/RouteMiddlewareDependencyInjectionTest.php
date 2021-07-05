<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Routing;

	use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateTestSubjects;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestMagicLink;
    use Tests\stubs\TestViewFactory;
    use Tests\UnitTest;
    use Tests\fixtures\Controllers\Admin\AdminControllerWithMiddleware;
	use Tests\fixtures\Middleware\MiddlewareWithDependencies;
    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Contracts\MagicLink;
    use BetterWP\Contracts\RouteUrlGenerator;
    use BetterWP\Support\WP;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Routing\Router;
    use BetterWP\Routing\UrlGenerator;
    use BetterWP\View\ViewFactory;

    class RouteMiddlewareDependencyInjectionTest extends UnitTest {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;
        use CreateUrlGenerator;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            $this->container->instance(UrlGenerator::class, $this->newUrlGenerator());
            $this->container->instance(MagicLink::class, new TestMagicLink());
            $this->container->instance(ViewFactory::class, new TestViewFactory());
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();

        }



        /** @test */
		public function middleware_is_resolved_from_the_service_container () {


		    $this->createRoutes(function () {

                $this->router->get( '/foo', function ( Request $request ) {

                    return $request->body;

                })->middleware(MiddlewareWithDependencies::class);

            });

			$request = $this->webRequest( 'GET', '/foo' );
			$this->runAndAssertOutput( 'foobar', $request );


		}

		/** @test */
		public function controller_middleware_is_resolved_from_the_service_container () {

		    $this->createRoutes(function () {

                $this->router->get( '/foo', AdminControllerWithMiddleware::class . '@handle');


            });


			$request = $this->webRequest( 'GET', '/foo' );
			$this->runAndAssertOutput( 'foobarbaz:controller_with_middleware', $request );

		}

		/** @test */
		public function after_controller_middleware_got_resolved_the_controller_is_not_instantiated_again_when_handling_the_request() {

			$GLOBALS['test'][ AdminControllerWithMiddleware::constructed_times ] = 0;


			$this->createRoutes(function () {

                $this->router->get( '/foo', AdminControllerWithMiddleware::class . '@handle');

            });



			$request = $this->webRequest( 'GET', '/foo' );
			$this->runAndAssertOutput( 'foobarbaz:controller_with_middleware', $request );

			$this->assertRouteActionConstructedTimes(1, AdminControllerWithMiddleware::class);


		}

        private function assertRouteActionConstructedTimes( int $times, $class ) {

            $actual = $GLOBALS['test'][ $class::constructed_times ] ?? 0;

            $this->assertSame(
                $times, $actual,
                'RouteAction [' . $class . '] was supposed to run: ' . $times . ' times. Actual: ' . $GLOBALS['test'][ $class::constructed_times ]
            );

        }

	}