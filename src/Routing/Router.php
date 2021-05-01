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
	use WPEmerge\Routing\ConditionFactory;
	use WPEmerge\Traits\HoldsRouteBlueprint;
	use WPEmerge\Traits\SortsMiddleware;

	/** @mixin \WPEmerge\Routing\RouteDecorator */
	class Router {

		use CompilesMiddleware;
		use SortsMiddleware;
		use HoldsRouteBlueprint;

		/** @var \WPEmerge\Routing\RouteGroup[] */
		private $group_stack;

		/** @var \Contracts\ContainerAdapter */
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

		/** @var RouteCollection */
		private $routes;

		public function __construct( ContainerAdapter $container, RouteCollection $routes ) {

			$this->container = $container;
			$this->routes    = $routes;

		}

		public function addRoute( array $methods, string $url, $action = null, $attributes = [] ) : Route {

			$url = $this->applyPrefix( $url );

			$route = new Route ( $methods, $url, $action );

			if ( $this->hasGroupStack() ) {

				$this->mergeGroupIntoRoute( $route );

			}

			if ( ! empty( $attributes ) ) {

				$this->populateUserAttributes( $route, $attributes);

			}

			// $route->addCondition( new UrlCondition( $url ) );

			return $this->routes->add( $route );


		}

		public function group( array $attributes, $routes ) {

			$this->updateGroupStack( new RouteGroup( $attributes ) );

			$this->loadRoutes( $routes );

			$this->deleteLastRouteGroup();

		}

		public function getRouteUrl( string $name, array $arguments = [] ) : string {

			$route = $this->routes->findByName( $name );

			if ( ! $route ) {

				throw new ConfigurationException(
					'There is no named route with the name: ' . $name . ' registered.'
				);

			}

			return $route->createUrl( $arguments );


		}

		public function runRoute( RequestInterface $request ) {

			[ $route, $payload ] = $this->routes->match( $request );

			if ( $route ) {

				/** @var Route $route */
				$route->payload($payload);

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

		private function populateUserAttributes( Route $route, array $attributes ) {

			(( new RouteAttributes($route) ))->populateInitial( $attributes );
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

		private function updateGroupStack( RouteGroup $group ) {

			if ( $this->hasGroupStack() ) {

				$group = $this->mergeWithLastGroup( $group );

			}

			$this->group_stack[] = $group;

		}

		private function hasGroupStack() : bool {

			return ! empty( $this->group_stack );

		}

		private function mergeWithLastGroup( RouteGroup $new_group ) : RouteGroup {

			return $new_group->mergeWith( $this->lastGroup() );

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

		private function applyPrefix( string $url ) : string {

			return Url::combinePath( $this->lastGroupPrefix(), $url );

		}

		private function mergeGroupIntoRoute( Route $route ) {

			( new RouteAttributes($route) )->mergeGroup($this->lastGroup());


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

