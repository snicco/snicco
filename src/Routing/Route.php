<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Contracts\SetsRouteAttributes;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Helpers\RouteSignatureParameters;
	use WPEmerge\Helpers\Url;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Routing\Conditions\UrlCondition;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\Str;

	class Route implements RouteInterface, SetsRouteAttributes {

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

		/** @var \WPEmerge\Contracts\ConditionInterface[] */
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
			$this->url        = $this->replaceOptional(Url::normalizePath($url));
			$this->action     = $action;
			$this->namespace  = $attributes['namespace'] ?? null;
			$this->middleware = $attributes['middleware'] ?? null;


		}

		public function handle( $action ) : Route {

			$this->action = $action;

			return $this;

		}

		public function and( ...$regex ) {

			$this->regex = $this->parseRegex( Arr::flattenOnePreserveKeys($regex));

			$this->compileUrl();

		}

		private function parseRegex( $regex ) : array {

			if ( is_int( Arr::firstEl( array_keys( $regex ) ) ) ) {

				return Arr::combineFirstTwo( $regex );

			}

			return $regex;

		}

		public function compileUrl () {

			$segments = UrlParser::segments($this->url);

			$url = $this->url;

			foreach ( $segments as $segment ) {


				$url = preg_replace_callback("/($segment(?=}))/", function ($match) {

					return $match[0] . ':' . $this->regex[$match[0]];

				}, $url , 1);

			}

			$this->compiled_url = rtrim($url, '/');

		}

		public function getCompiledUrl () :string  {

			$url = $this->compiled_url ?? $this->url;

			return rtrim($url, '/');

		}

		public function getMethods() : array {

			return $this->methods;

		}

		public function getAction()  {

			return $this->action;

		}

		public function methods( $methods ) {

			$this->methods = array_merge(
				$this->methods ?? [] ,
				array_map('strtoupper', Arr::wrap($methods) )
			);

		}

		public function namespace( string $namespace ) : Route {

			$this->namespace = $namespace;

			return $this;

		}

		public function addCondition( ConditionInterface $condition ) {

			$bucket = new ConditionBucket();
			$bucket->add($condition);

			$this->where( $bucket );


		}

		public function matches( RequestInterface $request ) : bool {


			$failed_condition = collect( $this->compiled_conditions )
				->first( function ( $condition ) use ( $request ) {

				return ! $condition->isSatisfied( $request );

			});

			return $failed_condition === null;


		}

		public function middleware( $middleware_names ) : Route {

			$middleware_names = Arr::wrap( $middleware_names );

			$this->middleware = array_merge( $this->middleware ?? [], $middleware_names );

			return $this;

		}

		public function getMiddleware() : array {

			return array_merge(
				$this->middleware ?? [],
				$this->controllerMiddleware()
			);


		}

		public function _run( RequestInterface $request ) {

			$params = collect( $this->signatureParameters() );

			$values = collect( [ $request ] )->merge( $this->getArguments( $request ) )
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

		private function getArguments( RequestInterface $request ) : array {


			$args = collect( $this->compiled_conditions )
				->flatMap( function ( ConditionInterface $condition ) use ( $request ) {

					return $condition->getArguments( $request );

				})
				->all();

			return $args;


		}

		private function signatureParameters() : array {

			return RouteSignatureParameters::fromCallable(
				$this->compiled_action->raw()
			);

		}

		public function name( string $name ) : Route {

			// Remove leading and trailing dots.
			$name = preg_replace('/^\.+|\.+$/', '', $name);

			$this->name = isset($this->name) ? $this->name. '.'  . $name : $name;

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

			$bucket = $args[0];

			if ( ! $bucket instanceof ConditionBucket ) {

				$this->conditions[] = Arr::flattenOnePreserveKeys($args);

				return $this;

			}

			foreach ( $bucket->all() as $condition ) {

				$this->conditions[] = $condition;

			}

			return $this;

		}

		public function compiledConditions( array $conditions ) {

			$this->compiled_conditions = $conditions;

		}

		public function createUrl($arguments) {

			$conditions = collect( $this->compiled_conditions );

			$url = $conditions->whereInstanceOf(UrlCondition::class)->first();

			if ( ! $url ) {

				throw new ConfigurationException(
					'The Route can not be converted to an URL.'
				);

			}

			return $url->toUrl($arguments);

		}

		public function url() : string {

			return $this->url;

		}

		public function getCompiledConditions() : array {

			return $this->compiled_conditions;
		}

		public function compileConditions( ConditionFactory $condition_factory ) : Route {

			$this->compiled_conditions = $condition_factory->compileConditions($this);

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


			$optionals = UrlParser::replaceOptionalMatch($url_pattern);

			foreach ( $optionals as $optional ) {

				$optional = preg_quote($optional, '/');

				$pattern = sprintf( "#(%s)#", $optional );

				$url_pattern = preg_replace_callback($pattern, function ($match) {

					$cleaned_match = Str::between($match[0], '{', '?');

					return sprintf( "[/{%s}]", $cleaned_match );

				}, $url_pattern,1  );

			}

			return rtrim($url_pattern, '/');

		}

	}