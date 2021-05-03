<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Handlers\HandlerFactory;
	use WPEmerge\Helpers\RouteSignatureParameters;

	class CompiledRoute implements RouteInterface {

		public $action;

		public $middleware;

		public $conditions;

		public $namespace;

		public function __construct( $attributes ) {

			$this->action     = $attributes['action'];
			$this->middleware = $attributes['middleware'] ?? [];
			$this->conditions = $attributes['conditions'] ?? [];
			$this->namespace  = $attributes['namespace'] ?? '';

		}

		public function satisfiedBy( RequestInterface $request ) : bool {

			$failed_condition = collect( $this->conditions )
				->first( function ( $condition ) use ( $request ) {

					return ! $condition->isSatisfied( $request );

				} );

			return $failed_condition === null;

		}

		public static function hydrate( array $attributes, HandlerFactory $handler_factory, ConditionFactory $condition_factory ) : CompiledRoute {

			$compiled = new static( $attributes );

			$compiled->action     = $handler_factory->create( $compiled->action, $compiled->namespace );
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

			return ! $this->action->raw() instanceof \Closure;

		}

		/** @todo Refactor this so that we dont rely on parameter order and parameter names. */
		public function run( RequestInterface $request, array $payload) {

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

			return $this->action->executeUsing( $payload->all() );

		}

		private function signatureParameters() : array {

			return RouteSignatureParameters::fromCallable(
				$this->action->raw()
			);

		}

		public function getConditions() {

			return $this->conditions;

		}

	}