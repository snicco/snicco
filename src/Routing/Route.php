<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Helpers\RouteSignatureParameters;
	use WPEmerge\Helpers\UrlParser;
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


		public function __construct( array $methods, string $url, $action , array $attributes = [] ) {

			$this->methods    = $methods;
			$this->url        = $url;
			$this->action     = $action;
			$this->namespace  = $attributes['namespace'] ?? null;
			$this->middleware = $attributes['middleware'] ?? null;

		}

		public function handle( $action ) : Route {

			$this->action = $action;

			return $this;

		}

		public function namespace( string $namespace ) : Route {

			$this->namespace = $namespace;

			return $this;

		}

		public function addCondition( ConditionInterface $condition, string $type ) {

			$this->conditions[$type] = $condition;

		}

		public function matches( RequestInterface $request ) {


			if ( ! in_array( $request->getMethod(), $this->methods ) ) {
				return false;
			}

			foreach ( $this->conditions as $condition ) {

				return $condition->isSatisfied( $request );

			}


		}

		public function middleware( $middleware_names ) : Route {

			$middleware_names = Arr::wrap( $middleware_names );

			$this->middleware = array_merge( $this->middleware ?? [] , $middleware_names );

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


			$args = collect( $this->conditions )
				->flatMap( function ( ConditionInterface $condition ) use ( $request ) {

					return $condition->getArguments( $request );

				} )
				->reject( function ( $value ) {

					return $value === [] || ! $value;

				} )
				->all();

				return $args;


		}

		private function signatureParameters() : array {

			return RouteSignatureParameters::fromCallable(
				$this->compiled_action->raw()
			);

		}

		public function name( string $name  ) :Route {

			$this->name = $name;

			return $this;

		}

		public function getName() : string {

			return $this->name;

		}

		public function getConditions(string $type = null )  {

			if ( $type ) {

				return $this->conditions[$type] ?? null;

			}

			return $this->conditions;

		}

		public function requiredSegments() : array {

			return UrlParser::segments($this->url);

		}


	}