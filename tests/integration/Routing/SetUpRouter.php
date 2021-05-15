<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

    use Closure;
    use Contracts\ContainerAdapter;
    use Tests\AssertsResponse;
    use Tests\CreateDefaultWpApiMocks;
    use Tests\TestRequest;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Http\Request;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
	use WPEmerge\Routing\Router;
    use WPEmerge\ServiceProviders\RoutingServiceProvider;

    trait SetUpRouter {

		use CreateDefaultWpApiMocks;
        use AssertsResponse;

		/**
		 * @var Router
		 */
		private $router;

		private function newRouterWith( Closure $routes ) {

			$this->newRouter($this->createContainer());

			$routes( $this->router );

		}

		private function newRouter(ContainerAdapter $container)  {

			$condition_factory = new ConditionFactory( $this->allConditions(), $container );
			$handler_factory   = new HandlerFactory( [], $container );
			$route_collection  = new RouteCollection(
				$condition_factory,
				$handler_factory,
				new FastRouteMatcher()
			);

			$router =  new Router(
			    $container,
                $route_collection,
                $this->responseFactory()
            );

			$this->router = $router;
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