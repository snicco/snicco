<?php
	


	namespace WPEmerge\Routing;

	use Closure;
	use WPEmerge\Contracts\HasRoutesInterface;
	use WPEmerge\Contracts\RouteHandler;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Routing\Conditions\ConditionFactory;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\UrlableInterface;
	use WPEmerge\Support\Arr;

	/**
	 * Provide routing for site requests (i.e. all non-api requests).
	 */
	class Router implements HasRoutesInterface {

		use HasRoutesTrait;

		/**
		 * Condition factory.
		 *
		 * @var ConditionFactory
		 */
		protected $condition_factory = null;

		/**
		 * Handler factory.
		 *
		 * @var HandlerFactory
		 */
		protected $handler_factory = null;

		/**
		 * Group stack.
		 *
		 * @var array<array<string, mixed>>
		 */
		protected $group_stack = [];

		/**
		 * Current active route.
		 *
		 * @var RouteInterface|null
		 */
		protected $current_route = null;

		/**
		 * Constructor.
		 *
		 * @codeCoverageIgnore
		 *
		 * @param  ConditionFactory  $condition_factory
		 * @param  HandlerFactory  $handler_factory
		 */
		public function __construct( ConditionFactory $condition_factory, HandlerFactory $handler_factory ) {

			$this->condition_factory = $condition_factory;
			$this->handler_factory   = $handler_factory;

		}

		/**
		 * Get the current route.
		 *
		 * @return RouteInterface
		 */
		public function getCurrentRoute() : ?RouteInterface {

			return $this->current_route;
		}

		/**
		 * Set the current route.
		 *
		 * @param  RouteInterface  $current_route
		 *
		 * @return void
		 */
		public function setCurrentRoute( RouteInterface $current_route ) {

			$this->current_route = $current_route;
		}

		/**
		 * Merge the methods attribute combining values.
		 *
		 * @param  string[]  $old
		 * @param  string[]  $new
		 *
		 * @return string[]
		 */
		public function mergeMethodsAttribute( $old, $new ) {

			return array_merge( $old, $new );
		}

		/**
		 * Merge the condition attribute.
		 *
		 * @param  string|array|Closure|ConditionInterface|null  $old
		 * @param  string|array|Closure|ConditionInterface|null  $new
		 *
		 * @return ConditionInterface
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function mergeConditionAttribute( $old, $new ) {

			try {
				$condition = $this->condition_factory->merge( $old, $new );
			}
			catch ( ConfigurationException $e ) {
				throw new ConfigurationException( 'Route condition is not a valid route string or condition.' );
			}

			return $condition;
		}

		/**
		 * Merge the middleware attribute combining values.
		 *
		 * @param  string[]  $old
		 * @param  string[]  $new
		 *
		 * @return string[]
		 */
		public function mergeMiddlewareAttribute(  $old,  $new ) : array {

			return array_merge( $old, $new );
		}

		/**
		 * Merge the namespace attribute taking the latest value.
		 *
		 * @param  string  $old
		 * @param  string  $new
		 *
		 * @return string
		 */
		public function mergeNamespaceAttribute( string $old, string $new ) : string {

			return ! empty( $new ) ? $new : $old;
		}

		/**
		 * Merge the handler attribute taking the latest value.
		 *
		 * @param  string|Closure  $old
		 * @param  string|Closure  $new
		 *
		 * @return string|Closure
		 */
		public function mergeHandlerAttribute( $old, $new ) {

			return ! empty( $new ) ? $new : $old;
		}

		/**
		 * Merge the handler attribute taking the latest value.
		 *
		 * @param  callable|null  $old
		 * @param  callable|null  $new
		 *
		 * @return string|Closure
		 */
		public function mergeQueryAttribute( ?callable $old, ?callable $new ) {

			if ( $new === null ) {
				return $old;
			}

			if ( $old === null ) {
				return $new;
			}

			return function ( $query_vars ) use ( $old, $new ) {

				return call_user_func( $new, call_user_func( $old, $query_vars ) );
			};
		}

		/**
		 * Merge the name attribute combining values with a dot.
		 *
		 * @param  string  $old
		 * @param  string  $new
		 *
		 * @return string
		 */
		public function mergeNameAttribute( $old, $new ) : string {

			$name = implode( '.', array_filter( [ $old, $new ] ) );

			// Trim dots.
			$name = preg_replace( '/^\.+|\.+$/', '', $name );

			// Reduce multiple dots to a single one.
			return preg_replace( '/\.{2,}/', '.', $name );
		}

		/**
		 * Merge attributes into route.
		 *
		 * @param  array<string, mixed>  $old
		 * @param  array<string, mixed>  $new
		 *
		 * @return array<string, mixed>
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function mergeAttributes( $old, $new ) : array {

			return [
				'methods' => $this->mergeMethodsAttribute(
					(array) Arr::get( $old, 'methods', [] ),
					(array) Arr::get( $new, 'methods', [] )
				),

				'condition' => $this->mergeConditionAttribute(
					Arr::get( $old, 'condition', null ),
					Arr::get( $new, 'condition', null )
				),

				'middleware' => $this->mergeMiddlewareAttribute(
					(array) Arr::get( $old, 'middleware', [] ),
					(array) Arr::get( $new, 'middleware', [] )
				),

				'namespace' => $this->mergeNamespaceAttribute(
					Arr::get( $old, 'namespace', '' ),
					Arr::get( $new, 'namespace', '' )
				),

				'handler' => $this->mergeHandlerAttribute(
					Arr::get( $old, 'handler', '' ),
					Arr::get( $new, 'handler', '' )
				),

				'query' => $this->mergeQueryAttribute(
					Arr::get( $old, 'query', null ),
					Arr::get( $new, 'query', null )
				),

				'name' => $this->mergeNameAttribute(
					Arr::get( $old, 'name', '' ),
					Arr::get( $new, 'name', '' )
				),
			];
		}

		/**
		 * Get the top group from the stack.
		 *
		 * @return array<string, mixed>
		 */
		protected function getGroup() {

			return Arr::last( $this->group_stack, null, [] );
		}

		/**
		 * Add a group to the group stack, merging all previous attributes.
		 *
		 *
		 * @param  array<string, mixed>  $group
		 *
		 * @return void
		 */
		protected function pushGroup( $group ) {

			$this->group_stack[] = $this->mergeAttributes( $this->getGroup(), $group );
		}

		/**
		 * Remove last group from the group stack.
		 *
		 * @return void
		 */
		protected function popGroup() {

			array_pop( $this->group_stack );
		}

		/**
		 * Create a route group.
		 *
		 *
		 * @param  array<string, mixed>  $attributes
		 * @param  Closure|string  $routes  Closure or path to file.
		 *
		 * @return void
		 */
		public function group( $attributes, $routes ) {

			$this->pushGroup( $attributes );

			if ( is_string( $routes ) ) {
				/** @noinspection PhpIncludeInspection */
				/** @codeCoverageIgnore */
				require_once $routes;
			} else {
				$routes();
			}

			$this->popGroup();
		}

		/**
		 * Make a route condition.
		 *
		 * @param  mixed  $condition
		 *
		 * @return ConditionInterface
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		protected function routeCondition( $condition ) : ConditionInterface {

			if ( $condition === null ) {
				throw new ConfigurationException( 'No route condition specified. Did you miss to call url() or where()?' );
			}

			if ( ! $condition instanceof ConditionInterface ) {
				$condition = $this->condition_factory->make( $condition );
			}

			return $condition;
		}

		/**
		 * Make a route handler.
		 *
		 * @codeCoverageIgnore
		 *
		 * @param  string|Closure|null  $handler
		 * @param  string  $namespace
		 *
		 * @return Handler
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		protected function _routeHandler( $handler, $namespace ) : Handler {

			if ( $handler === null ) {
				throw new ConfigurationException( 'No route handler specified. Did you miss to call handle()?' );
			}

			return $this->handler_factory->make( $handler, '', $namespace );
		}


		protected function routeHandler( $handler, $namespace ) : RouteHandler {

			if ( $handler === null ) {
				throw new ConfigurationException( 'No route handler specified. Did you miss to call handle()?' );
			}

			return $this->handler_factory->createRouteHandlerUsing( $handler );
		}

		/**
		 * Make a route.
		 *
		 * @param  array<string, mixed>  $attributes
		 *
		 * @return RouteInterface
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function route(  $attributes ) : RouteInterface {

			$attributes = $this->mergeAttributes( $this->getGroup(), $attributes );
			$attributes = array_merge(
				$attributes,
				[
					'condition' => $this->routeCondition( $attributes['condition'] ),
					'handler'   => $this->routeHandler( $attributes['handler'], $attributes['namespace'] ),
				]
			);

			if ( empty( $attributes['methods'] ) ) {
				throw new ConfigurationException(
					'Route does not have any assigned request methods. ' .
					'Did you miss to call get() or post() on your route definition, for example?'
				);
			}

			return new Route($attributes);
		}

		/**
		 * Assign and return the first satisfied route (if any) as the current one for the given
		 * request.
		 *
		 * @param  RequestInterface  $request
		 *
		 * @return \WPEmerge\Contracts\RouteInterface|null
		 */
		public function hasMatchingRoute( $request ) : ?RouteInterface {

			$routes = $this->getRoutes();

			foreach ( $routes as $route ) {

				if ( $route->isSatisfied( $request ) ) {

					$this->setCurrentRoute( $route );

					$request->setRoute($route);

					return $route;

				}
			}

			return null;

		}

		/**
		 * Get the url for a named route.
		 *
		 * @param  string  $name
		 * @param  array  $arguments
		 *
		 * @return string
		 */
		public function getRouteUrl( $name, $arguments = [] ) {

			$routes = $this->getRoutes();

			foreach ( $routes as $route ) {
				if ( $route->getAttribute( 'name' ) !== $name ) {
					continue;
				}

				$condition = $route->getAttribute( 'condition' );

				if ( ! $condition instanceof UrlableInterface ) {
					throw new ConfigurationException(
						'Route condition is not resolvable to a URL.'
					);
				}

				return $condition->toUrl( $arguments );
			}

			throw new ConfigurationException( "No route registered with the name \"$name\"." );
		}

	}
