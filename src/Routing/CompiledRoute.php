<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use Closure;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Str;
	use Opis\Closure\SerializableClosure;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteCondition;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Routing\RouteSignatureParameters;

	class CompiledRoute implements RouteCondition {

		/** @var \WPEmerge\Contracts\RouteAction|string */
		public $action;

		public $middleware;

		public $conditions;

		public $namespace;

		/**
		 * @var array
		 */
		public $defaults;


		public function __construct( $attributes ) {

			$this->action     = $attributes['action'];
			$this->middleware = $attributes['middleware'] ?? [];
			$this->conditions = $attributes['conditions'] ?? [];
			$this->namespace  = $attributes['namespace'] ?? '';
			$this->defaults   = $attributes['defaults'] ?? [];

		}

		public function satisfiedBy( RequestInterface $request ) : bool {

			$failed_condition = collect( $this->conditions )
				->first( function ( $condition ) use ( $request ) {

					return ! $condition->isSatisfied( $request );

				} );

			return $failed_condition === null;

		}

		public static function hydrate(
			array $attributes,
			HandlerFactory $handler_factory,
			ConditionFactory $condition_factory
		) : CompiledRoute {

			$compiled = new static( $attributes );

			if ( $compiled->isSerializedClosure( $action = $compiled->action ) ) {

				$action = \Opis\Closure\unserialize( $action );

			}

			$compiled->action     = $handler_factory->create( $action, $compiled->namespace );
			$compiled->conditions = $condition_factory->compileConditions( $compiled );

			return $compiled;

		}

		public function middleware() : array {

			return array_merge(
				$this->middleware,
				$this->controllerMiddleware()

			);

		}

		private function controllerMiddleware() : array {

			if ( ! $this->usesController() ) {

				return [];
			}

			return $this->action->resolveControllerMiddleware();

		}

		private function usesController() : bool {

			return ! $this->action->raw() instanceof Closure;

		}

		/** @todo Refactor this so that we dont rely on parameter order and parameter names. */
		public function run( RequestInterface $request, array $payload ) {

			$params = collect( $this->signatureParameters() );

			$values = collect( [ $request ] )->merge( $payload )
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

			return $this->action->executeUsing( $this->mergeDefaults($payload)->all() );

		}

		private function signatureParameters() : array {

			return RouteSignatureParameters::fromCallable(
				$this->action->raw()
			);

		}

		public function getConditions() {

			return $this->conditions;

		}

		public function cacheable() : CompiledRoute {

			if ( $this->action instanceof Closure ) {

				$closure = new SerializableClosure( $this->action );

				$this->action = \Opis\Closure\serialize( $closure );

			}

			return $this;

		}

		private function isSerializedClosure( $action ) : bool {

			return is_string( $action )
			       && Str::startsWith( $action, 'C:32:"Opis\\Closure\\SerializableClosure' ) !== false;
		}

		private function mergeDefaults (Collection $route_payload) : Collection {

			$new = $route_payload->merge($this->defaults);

			return $new;

		}

	}