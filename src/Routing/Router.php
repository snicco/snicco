<?php


	namespace WPEmerge\Routing;

	use Closure;
	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Helpers\Pipeline;
	use WPEmerge\Helpers\Url;
	use WPEmerge\Routing\Conditions\UrlCondition;
	use WPEmerge\Support\Arr;
	use WPEmerge\Traits\CompilesMiddleware;
	use WPEmerge\Routing\Conditions\ConditionFactory;
	use WPEmerge\Traits\HoldsRouteBlueprint;
	use WPEmerge\Traits\SortsMiddleware;

	/** @mixin \WPEmerge\Routing\RouteDecorator */
	class Router {

		use CompilesMiddleware;
		use SortsMiddleware;
		use HoldsRouteBlueprint;

		/** @var ConditionFactory  */
		private $condition_factory;

		/** @var HandlerFactory  */
		private $handler_factory;

		/** @var \WPEmerge\Routing\RouteGroup[] */
		private $group_stack;

		/** @var \Contracts\ContainerAdapter  */
		private $container;

		/**
		 * @var string[]
		 */
		private $middleware_groups = [];

		/**
		 * @var string[]
		 */
		private $middleware_priority = [];

		/**
		 * @var string[]
		 */
		private $route_middleware_aliases = [];

		/** @var Route[] */
		private $routes;

		public function __construct(
			ContainerAdapter $container,
			ConditionFactory $condition_factory,
			HandlerFactory $handler_factory
		) {

			$this->condition_factory = $condition_factory;
			$this->handler_factory   = $handler_factory;
			$this->container         = $container;
		}


		public function group( array $attributes, $routes ) {

			$this->updateGroupStack( new RouteGroup($attributes ) );

			$this->loadRoutes( $routes );

			$this->deleteLastRouteGroup();

		}

		public function getRouteUrl( string $name, array $arguments = [] ) : string {


			$route = collect( $this->routes )->first( function ( Route $route ) use ( $name ) {

				return $route->getName() === $name;

			} );

			if ( ! $route ) {

				throw new ConfigurationException(
					'There is no named route with the name: ' . $name . ' registered.'
				);

			}

			$route->compileConditions( $this->condition_factory );

			return $route->createUrl( $arguments );


		}

		public function runRoute( RequestInterface $request ) {

			if ( $route = $this->findRoute( $request ) ) {

				$route->compileAction( $this->handler_factory );

				return $this->runWithinStack( $route, $request );

			}

			return null;

		}

		public function middlewareGroup( string $name, array $middleware ) : void {

			$this->middleware_groups[ $name ] = $middleware;

		}

		public function middlewarePriority( array $middleware_priority ) : void {

			$this->middleware_priority = $middleware_priority;

		}

		public function aliasMiddleware( $name, $class ) : void {

			$this->route_middleware_aliases[ $name ] = $class;

		}

		private function loadRoutes( $routes ) {

			if ( $routes instanceof Closure ) {

				$routes( $this );

			} else {

				( new RouteRegistrar( $this ) )->loadRouteFile( $routes );

			}

		}

		private function deleteLastRouteGroup() {

			array_pop( $this->group_stack );

		}

		private function updateGroupStack( RouteGroup $group) {

			if ( $this->hasGroupStack() ) {

				$group = $this->mergeWithLastGroup( $group );

			}

			$this->group_stack[] = $group;

		}

		private function hasGroupStack() : bool {

			return ! empty( $this->group_stack );

		}

		private function mergeWithLastGroup( RouteGroup $new_group) : RouteGroup {

			return $new_group->mergeWith($this->lastGroup());

		}

		/**
		 * @todo Find a way to not recompile shared conditions that were passed by
		 * @todo previous routes but the route in total didnt match.
		 */
		private function findRoute( RequestInterface $request ) : ?Route {


			$routes = collect( $this->routes );

			$route = $routes->filter( function ( Route $route ) use ( $request ) {

				return $this->matchingHttpVerbs( $request, $route );

			} )
			                ->first( function ( Route $route ) use ( $request ) {

				                $route->compileConditions( $this->condition_factory );

				                return $route->matches( $request );

			                } );

			if ( $route ) {

				$request->setRoute( $route );

			}

			return $route;

		}

		private function matchingHttpVerbs( RequestInterface $request, Route $route ) : bool {

			return Arr::isValue( $request->getMethod(), $route->getMethods() );

		}

		private function runWithinStack( RouteInterface $route, RequestInterface $request ) {

			$middleware = $route->getMiddleware();
			$middleware = $this->mergeGlobalMiddleware( $middleware );
			$middleware = $this->expandMiddleware( $middleware );
			$middleware = $this->uniqueMiddleware( $middleware );
			$middleware = $this->sortMiddleware( $middleware );

			return ( new Pipeline( $this->container ) )
				->send( $request )
				->through( $this->skipMiddleware() ? [] : $middleware )
				->then( function ( $request ) use ( $route ) {

					return $route->run( $request );

				} );

		}

		private function skipMiddleware() : bool {

			return $this->container->offsetExists( 'middleware.disable' );

		}

		private function addRoute( array $methods, string $url, $action = null ) : Route {

			$url = $this->applyPrefix( $url );

			$route = new Route ( $methods, $url, $action );

			if ( $this->hasGroupStack() ) {

				$this->mergeGroupIntoRoute( $route );

			}

			$route->addCondition( new UrlCondition( $url ) );

			return $this->routes[] = $route;


		}

		private function applyPrefix( string $url ) : string {

			return Url::combinePath( $this->lastGroupPrefix(), $url );

		}

		private function mergeGroupIntoRoute( Route $route ) {

			$this->lastGroup()->mergeIntoRoute($route);

		}

		private function lastGroup() {

			return end( $this->group_stack );

		}

		private function lastGroupPrefix() : string {

			if ( ! $this->hasGroupStack() ) {

				return '';

			}

			return $this->lastGroup()->prefix();


		}

		public function __call( $method, $parameters ) {


			if ( ! in_array( $method, RouteDecorator::allowed_attributes ) ) {

				throw new \BadMethodCallException(
					'Method: ' . $method . 'does not exists on ' . get_class( $this )
				);

			}

			if ( $method === 'where' || $method === 'middleware' ) {

				return ( ( new RouteDecorator( $this ) )->decorate(
					$method,
					is_array( $parameters[0] ) ? $parameters[0] : $parameters )
				);

			}

			return ( ( new RouteDecorator( $this ) )->decorate( $method, $parameters[0] ) );

		}


	}

