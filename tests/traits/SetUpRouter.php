<?php


	declare( strict_types = 1 );


	namespace Tests\traits;

    use Contracts\ContainerAdapter;
    use Tests\stubs\TestRequest;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Factories\RouteActionFactory;
	use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Http\Psr7\Request;
	use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\RouteBuilder;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\RoutingServiceProvider;

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
			$handler_factory   = new RouteActionFactory( [], $container );


            $route_collection  = new RouteCollection(
                $this->createRouteMatcher(),
                $condition_factory,
                $handler_factory
            );

            $router =  new Router(
                $container,
                $route_collection,
                $response= $this->createResponseFactory()
            );

            $this->routes = $route_collection;

			$container->instance(RouteActionFactory::class, $handler_factory);
			$container->instance(ConditionFactory::class, $condition_factory);
			$container->instance(AbstractRouteCollection::class, $route_collection);
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



	}