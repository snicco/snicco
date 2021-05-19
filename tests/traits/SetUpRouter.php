<?php


	declare( strict_types = 1 );


	namespace Tests\traits;

    use Contracts\ContainerAdapter;
    use Tests\stubs\TestRequest;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Http\Request;
	use WPEmerge\Routing\FastRoute\FastRouteMatcher;
	use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\RouteCompiler;
    use WPEmerge\Routing\Router;
    use WPEmerge\ServiceProviders\RoutingServiceProvider;

    trait SetUpRouter {

		use CreateDefaultWpApiMocks;
        use AssertsResponse;

		/**
		 * @var Router
		 */
		private $router;

		/** @var RouteCollection */
        private $routes;

		private function newRouter(ContainerAdapter $container = null)  {

		    $container = $container ?? $this->createContainer();

			$condition_factory = new ConditionFactory( $this->allConditions(), $container );
			$handler_factory   = new HandlerFactory( [], $container );

			$compiler =  new RouteCompiler($handler_factory, $condition_factory);

            $route_collection  = new RouteCollection(
                $this->createRouteMatcher($compiler),
                $compiler
            );

            $router =  new Router(
                $container,
                $route_collection,
                $response= $this->responseFactory()
            );

            $this->routes = $route_collection;

			$container->instance(HandlerFactory::class, $handler_factory);
			$container->instance(ConditionFactory::class, $condition_factory);
			$container->instance(RouteCollection::class, $route_collection);
			$container->instance(ResponseFactory::class, $response);

			return $this->router = $router;

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