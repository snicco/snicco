<?php


	namespace WPEmerge\Routing;

	use Closure;
	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\HasRoutesInterface;
	use WPEmerge\Contracts\RouteAction;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Helpers\Pipeline;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Routing\Conditions\UrlCondition;
	use WPEmerge\Support\Arr;
	use WPEmerge\Traits\CompilesMiddleware;
	use WPEmerge\Routing\Conditions\ConditionFactory;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\UrlableInterface;
	use WPEmerge\Support\WPEmgereArr;
	use WPEmerge\Traits\HoldsRouteBlueprint;
	use WPEmerge\Traits\SortsMiddleware;

	/** @mixin \WPEmerge\Routing\RouteDecorator */
	class Router {

		use CompilesMiddleware;
		use SortsMiddleware;
		use HoldsRouteBlueprint;

		/**
		 * Condition factory.
		 *
		 * @var ConditionFactory
		 */
		private $condition_factory;

		/**
		 * Handler factory.
		 *
		 * @var HandlerFactory
		 */
		private $handler_factory;

		/**
		 * Group stack.
		 *
		 * @var array<array<string, mixed>>
		 */
		private $group_stack = [];

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;

		/**
		 * @var string[]
		 */
		private $middleware_groups = [];

		/**
		 * @var string[]
		 */
		private $middleware_priority = [];

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



		public function group( $attributes, $routes ) {

			$this->updateGroupStack( $attributes );

			// Once we have updated the group stack, we'll load the provided routes and
			// merge in the group's attributes when the routes are created. After we
			// have created the routes, we will pop the attributes off the stack.
			$this->loadRoutes( $routes );

			$this->deleteLastRouteGroup();

		}

		public function getRouteUrl( string $name, array $arguments = [] ) : string {


			$route = collect( $this->routes )->first( function ( Route $route ) use ( $name ) {

				return $route->getName() === $name;

			}, null );

			if ( ! $route ) {

				throw new ConfigurationException(
					'There is no named route with the name: ' . $name . ' registered.'
				);

			}

			$route->compileConditions( $this->condition_factory );

			return $route->createUrl( $arguments );


		}

		public function runRoute( RequestInterface $request ) {

			$route = $this->findRoute( $request );

			if ( ! $route ) {

				return null;

			}

			return $this->runWithinStack( $route, $request );

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

				( new RouteFileRegistrar( $this ) )->register( $routes );

			}

		}

		private function deleteLastRouteGroup() {

			array_pop( $this->group_stack );

		}

		private function updateGroupStack( $attributes ) {

			if ( $this->hasGroupStack() ) {

				$attributes = $this->mergeWithLastGroup( $attributes );

			}

			$this->group_stack[] = $attributes;

		}

		private function hasGroupStack() : bool {

			return ! empty( $this->group_stack );

		}

		private function mergeWithLastGroup( $new, $prependExistingPrefix = true ) {

			// return RouteGroup::merge($new, end($this->groupStack), $prependExistingPrefix);
			return $new;

		}

		private function findRoute( RequestInterface $request ) : ?RouteInterface {


			$route = collect( $this->routes )
				->filter( function ( Route $route ) use ( $request ) {

					// only correct http methods
					return Arr::isValue( $request->getMethod(), $route->getMethods() );

				} )
				->first( function ( Route $route ) use ( $request ) {

					$route->compileConditions( $this->condition_factory );

					return $route->matches( $request );

				}, null );

			if ( $route ) {

				$request->setRoute( $route );

			}

			return $route;

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

					$route->compileAction( $this->handler_factory );

					return $route->run( $request );

				} );

		}

		private function skipMiddleware() : bool {

			return $this->container->offsetExists( 'middleware.disable' );

		}

		private function addRoute( array $methods, string $url, $action = null ) : Route {

			$url = UrlParser::normalize( $url );

			$route = $this->newRoute(
				$methods,
				$this->applyPrefix( $url ),
				$action
			);

			if ( $this->hasGroupStack() ) {

				$this->mergeGroupAttributesIntoRoute( $route );

			}

			$this->routes[] = $route;

			return $route;


		}

		private function newRoute( $methods, $url, $action ) : Route {

			return new Route ( $methods, $url, $action );

		}

		private function applyPrefix( $url ) : string {

			return trim( trim( $this->getLastGroupPrefix(), '/' ) . '/' . trim( $url, '/' ), '/' ) ? : '/';
		}

		private function mergeGroupAttributesIntoRoute( Route $route ) {

			$group = $this->getLastGroup();

			$route->addMethods( $group['methods'] ?? [] );
			$route->middleware( $group['middleware'] ?? [] );

			if ( isset( $group['namespace'] ) ) {

				$route->namespace( $group['namespace'] );

			}

			if ( isset( $group['name'] ) ) {

				$route->name( $group['name'] );

			}

			if ( isset( $group['where'] ) ) {

				$this->mergeConditions($group['where'], $route);

			}



		}

		private function mergeConditions ( ConditionBucket $bucket , Route $route ) {

			foreach ( $bucket->all() as $condition ) {

				$route->where($condition);

			}

		}

		private function getLastGroup() {

			return end( $this->group_stack );

		}

		private function getLastGroupPrefix() : string {


			if ( ! $this->hasGroupStack() ) {

				return '';

			}

			$last = $this->getLastGroup();

			return $last['prefix'] ?? '';


		}


	}

