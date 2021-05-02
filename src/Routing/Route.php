<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Contracts\SetsRouteAttributes;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Helpers\RouteSignatureParameters;
	use WPEmerge\Helpers\Url;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\Str;
	use WPEmerge\Traits\CompilesExecutableRoute;

	class Route implements RouteInterface, SetsRouteAttributes {

		use CompilesExecutableRoute;

		/**
		 * @var array
		 */
		private $methods;

		/**
		 * @var string
		 */
		private $url;

		/** @var string|Closure|array */
		private $action;

		/** @var \WPEmerge\Contracts\RouteAction */
		private $compiled_action;

		/** @var \WPEmerge\Routing\ConditionBlueprint[] */
		private $conditions;

		/**
		 * @var array
		 */
		private $middleware;

		private $namespace;

		/** @var string */
		private $name;

		/**
		 * @var ConditionInterface[]
		 */
		private $compiled_conditions = [];

		/**
		 * @var array
		 */
		private $regex;

		/**
		 * @var string
		 */
		private $compiled_url;

		private $payload;

		public function __construct( array $methods, string $url, $action, array $attributes = [] ) {

			$this->methods    = $methods;
			$this->url        = $this->replaceOptional( Url::normalizePath( $url ) );
			$this->action     = $action;
			$this->namespace  = $attributes['namespace'] ?? null;
			$this->middleware = $attributes['middleware'] ?? null;

		}

		public function compile () : array {

			$compiled_route = new CompiledRoute(
				$this->action,
				$this->middleware,
				$this->conditions
			);

			return $compiled_route;

		}

		public function handle( $action ) : Route {

			$this->action = $action;

			return $this;

		}

		public function and( ...$regex ) : Route {

			$this->regex = $this->parseRegex( Arr::flattenOnePreserveKeys( $regex ) );

			$this->compileUrl();

			return $this;

		}

		private function parseRegex( $regex ) : array {

			if ( is_int( Arr::firstEl( array_keys( $regex ) ) ) ) {

				return Arr::combineFirstTwo( $regex );

			}

			return $regex;

		}

		public function compileUrl() {

			$segments = UrlParser::segments( $this->url );

			$segments = array_filter( $segments, function ( $segment ) {

				return isset( $this->regex[ $segment ] );

			} );

			$url = $this->url;

			foreach ( $segments as $segment ) {

				$pattern = sprintf( "/(%s(?=\\}))/", preg_quote( $segment, '/' ) );;

				$url = preg_replace_callback( $pattern, function ( $match ) {

					return $match[0] . ':' . $this->regex[ $match[0] ];

				}, $url, 1 );

			}

			$this->compiled_url = rtrim( $url, '/' );

		}

		public function getCompiledUrl() : string {

			$url = $this->compiled_url ?? $this->url;

			return rtrim( $url, '/' );

		}

		public function getMethods() : array {

			return $this->methods;

		}

		public function methods( $methods ) {

			$this->methods = array_merge(
				$this->methods ?? [],
				array_map( 'strtoupper', Arr::wrap( $methods ) )
			);

		}

		public function namespace( string $namespace ) : Route {

			$this->namespace = $namespace;

			return $this;

		}

		public function matches( RequestInterface $request ) : bool {


			$failed_condition = collect( $this->compiled_conditions )
				->first( function ( $condition ) use ( $request ) {

					return ! $condition->isSatisfied( $request );

				} );

			return $failed_condition === null;


		}

		public function middleware( $middleware ) : Route {

			$middleware = Arr::wrap( $middleware );

			$this->middleware = array_merge( $this->middleware ?? [], $middleware );

			return $this;

		}

		public function getMiddleware() : array {

			return array_merge(
				$this->middleware ?? [],
				$this->controllerMiddleware()
			);


		}

		/** @todo Refactor this so that we dont rely on parameter order and parameter names. */
		public function run( RequestInterface $request ) {

			$params = collect( $this->signatureParameters() );

			$values = collect( [ $request ] )->merge( $this->payload )
			                                 ->values();

			if ( $params->count() < $values->count() ) {

				$values = $values->slice( 0, count( $params ) );

			}

			if ( $params->count() > $values->count() ) {

				$params = $params->slice( 0, count( $values ) );

			}

			$payload = $params
				->map( function ( $param ) {

					return $param->getName();

				} )
				->values()
				->combine( $values );

			return $this->compiled_action->executeUsing( $payload->all() );

		}

		public function compileAction( HandlerFactory $factory ) : Route {

			$this->compiled_action = $factory->create( $this->action, $this->namespace );

			return $this;

		}

		private function signatureParameters() : array {

			return RouteSignatureParameters::fromCallable(
				$this->compiled_action->raw()
			);

		}

		public function name( string $name ) : Route {

			// Remove leading and trailing dots.
			$name = preg_replace( '/^\.+|\.+$/', '', $name );

			$this->name = isset( $this->name ) ? $this->name . '.' . $name : $name;

			return $this;


		}

		public function getName() {

			return $this->name;

		}

		public function getConditions( string $type = null ) {

			if ( $type ) {

				return $this->conditions[ $type ] ?? null;

			}

			return $this->conditions;

		}

		public function where() : Route {

			$args = func_get_args();

			$this->conditions[] = new ConditionBlueprint($args);

			return $this;

		}

		public function url() : string {

			return $this->url;

		}

		public function getCompiledConditions() : array {

			return $this->compiled_conditions;
		}

		public function compileConditions( ConditionFactory $condition_factory ) : Route {

			$this->compiled_conditions = $condition_factory->compileConditions( $this );

			return $this;

		}

		private function controllerMiddleware() : array {

			if ( ! $this->usesController() ) {

				return [];
			}

			return $this->compiled_action->resolveControllerMiddleware();

		}

		private function usesController() : bool {

			return ! $this->compiled_action->raw() instanceof \Closure;

		}

		public function payload( $payload ) {

			$this->payload = $payload;

		}

		private function replaceOptional( string $url_pattern ) : string {


			$optionals = UrlParser::replaceOptionalMatch( $url_pattern );

			foreach ( $optionals as $optional ) {

				$optional = preg_quote( $optional, '/' );

				$pattern = sprintf( "#(%s)#", $optional );

				$url_pattern = preg_replace_callback( $pattern, function ( $match ) {

					$cleaned_match = Str::between( $match[0], '{', '?' );

					return sprintf( "[/{%s}]", $cleaned_match );

				}, $url_pattern, 1 );

			}

			while ( $this->hasMultipleOptionalSegments( rtrim( $url_pattern, '/' ) ) ) {

				$this->mergeOptional( $url_pattern );

			}

			return rtrim( $url_pattern, '/' );

		}

		private function hasMultipleOptionalSegments( string $url_pattern ) : bool {

			$count = preg_match_all( '/(?<=\[).*?(?=])/', $url_pattern, $matches );

			return $count > 1;

		}

		private function mergeOptional( string &$url_pattern ) {

			preg_match( '/(\[(.*?)])/', $url_pattern, $matches );

			$first = $matches[0];

			$before = Str::before( $url_pattern, $first );
			$after  = Str::afterLast( $url_pattern, $first );

			$url_pattern = $before . rtrim( $first, ']' ) . rtrim( $after, '/' ) . ']';

		}

	}