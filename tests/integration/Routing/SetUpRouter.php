<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

    use Closure;
    use Contracts\ContainerAdapter;
    use Tests\AssertsResponse;
    use Tests\CreateContainer;
    use Tests\CreatePsr17Factories;
    use Tests\SetUpDefaultMocks;
    use Tests\stubs\TestViewService;
    use Tests\TestRequest;
	use WPEmerge\Facade\WP;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Http\HttpResponseFactory;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;
    use WPEmerge\ServiceProviders\RoutingServiceProvider;

    trait SetUpRouter {

		use SetUpDefaultMocks;
        use CreateContainer;
        use CreatePsr17Factories;
        use AssertsResponse;


		/**
		 * @var Router
		 */
		private $router;

		/** @var ContainerAdapter */
		private $container;

		/** @var RouteCollection */
		private $route_collection;

		protected function setUp() : void {

			parent::setUp();

			$this->newRouter();

			WP::setFacadeContainer($this->container);

		}

		private function newRouterWith( Closure $routes ) {

			$this->newRouter();

			$routes( $this->router );

		}

		private function newRouter() {

			$conditions = is_callable( [
				$this,
				'conditions',
			]) ? $this->conditions() : $this->allConditions();
			$container         = $this->createContainer();
			$condition_factory = new ConditionFactory( $conditions, $container );
			$handler_factory   = new HandlerFactory( [], $container );
			$route_collection  = new RouteCollection(
				$condition_factory,
				$handler_factory,
				new FastRouteMatcher()
			);
			$this->route_collection = $route_collection;
			$this->container   = $container;
			$this->router      = new Router(
			    $container,
                $route_collection,
                new HttpResponseFactory(
                    new TestViewService(),
                    $this->psrResponseFactory(),
                    $this->psrStreamFactory(),
                )
            );

		}

		private function request( $method, $path ) : Request {

			return TestRequest::from( $method, $path );

		}

		private function allConditions() : array {

		    return array_merge(RoutingServiceProvider::CONDITION_TYPES , [

                'true'                 => \Tests\stubs\Conditions\TrueCondition::class,
                'false'                => \Tests\stubs\Conditions\FalseCondition::class,
                'maybe'                => \Tests\stubs\Conditions\MaybeCondition::class,
                'unique'               => \Tests\stubs\Conditions\UniqueCondition::class,
                'dependency_condition' => \Tests\stubs\Conditions\ConditionWithDependency::class,

            ]);


		}

		private function assertRouteActionConstructedTimes( int $times, $class ) {

			$actual = $GLOBALS['test'][ $class::constructed_times ] ?? 0;

			$this->assertSame(
				$times, $actual,
				'RouteAction [' . $class . '] was supposed to run: ' . $times . ' times. Actual: ' . $GLOBALS['test'][ $class::constructed_times ]
			);

		}

	}