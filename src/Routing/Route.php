<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Helpers\RouteSignatureParameters;
	use WPEmerge\Helpers\Url;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Routing\Conditions\ConditionFactory;
	use WPEmerge\Routing\Conditions\UrlCondition;
	use WPEmerge\Support\Arr;

	class Route implements RouteInterface {

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
		 * @var array
		 */
		private $compiled_conditions = [];


		public function __construct( array $methods, string $url, $action, array $attributes = [] ) {

			$this->methods    = $methods;
			$this->url        = $url;
			$this->action     = $action;
			$this->namespace  = $attributes['namespace'] ?? null;
			$this->middleware = $attributes['middleware'] ?? null;

			$this->addCondition( new UrlCondition($this->url) );

		}

		public function handle( $action ) : Route {

			$this->action = $action;

			return $this;

		}

		public function getMethods() : array {

			return $this->methods;

		}

		public function addMethods(array $methods) {

			$this->methods = array_merge($this->methods ?? [] , $methods);

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

			$failed = collect( $this->compiled_conditions )->reject( function ( $condition ) use ( $request ) {

				return $condition->isSatisfied( $request );

			} );

			return $failed->isEmpty() === true;


		}

		public function middleware( $middleware_names ) : Route {

			$middleware_names = Arr::wrap( $middleware_names );

			$this->middleware = array_merge( $this->middleware ?? [], $middleware_names );

			return $this;

		}

		public function getMiddleware() {

			return $this->middleware ?? [];

		}

		public function run( RequestInterface $request ) {

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

		public function compileAction( HandlerFactory $factory ) {

			$this->compiled_action = $factory->create( $this->action, $this->namespace );

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

		public function getName() : string {

			return $this->name;

		}

		public function getConditions( string $type = null ) {

			if ( $type ) {

				return $this->conditions[ $type ] ?? null;

			}

			return $this->conditions;

		}

		public function requiredSegments() : array {

			return UrlParser::requiredSegments( $this->url );

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

			$condition = $conditions->first( function ( ConditionInterface $condition ) {

				return $condition instanceof UrlCondition;

			}, null );

			if ( ! $condition ) {

				throw new ConfigurationException(
					'The Route can not be converted to an URL.'
				);

			}

			return $condition->toUrl($arguments);

		}

		public function url() : string {

			return $this->url;

		}

		public function getCompiledConditions() : array {

			return $this->compiled_conditions;
		}

		public function compileConditions( ConditionFactory $condition_factory ) {

			$this->compiled_conditions = $condition_factory->compileConditions($this);

		}

	}