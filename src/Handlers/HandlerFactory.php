<?php


	namespace WPEmerge\Handlers;

	use Closure;
	use Contracts\ContainerAdapter;
	use Exception;
	use WPEmerge\Contracts\RouteHandler;
	use WPEmerge\Helpers\Reflector;
	use WPEmerge\Support\Str;

	class HandlerFactory {


		/**
		 * Array of FQN for the three Controller Types.
		 *
		 * @var array
		 */
		private $controller_namespaces;

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;

		public function __construct( array $controller_namespaces , ContainerAdapter $container ) {

			$this->controller_namespaces = $controller_namespaces;
			$this->container = $container;

		}

		/**
		 * @param  string|array|callable  $raw_handler
		 *
		 * @return \WPEmerge\Contracts\RouteHandler
		 * @throws \Exception
		 */
		public function createRouteHandlerUsing( $raw_handler ) : RouteHandler {

			$handler = $this->normalizeInput( $raw_handler );

			if ( $handler[0] instanceof Closure ) {

				return new ClosureHandler( $handler[0], $this->wrapClosure($handler[0]) );

			}

			if ( ! ( $this->checkIsCallable( $handler ) ) ) {

				$this->fail($handler[0], $handler[1]);

			}

			return new Controller( $handler, $this->wrapController($handler));

		}

		private function normalizeInput( $raw_handler ) : array {

			return collect( $raw_handler )
				->flatMap( function ( $value ) {

					if ( $value instanceof Closure || ! Str::contains( $value, '@' ) ) {

						return [ $value ];

					}

					return [ Str::before( $value, '@' ), Str::after( $value, '@' ) ];

				} )
				->filter( function ( $value ) {

					return ! empty( $value );

				} )
				->values()
				->all();

		}

		private function checkIsCallable( array $handler ) : bool {

			if ( Reflector::isCallable( $handler ) ) {
				return true;
			}

			$matched = collect( $this->controller_namespaces )->filter( function ( $namespace ) use ( $handler ) {

				return Reflector::isCallable( [ $namespace . '\\' . $handler[0] , $handler[1] ] );

			} );

			return $matched->isNotEmpty();


		}

		private function fail( $class, $method) {

			$method = Str::replaceFirst( '@', '', $method );

			throw new Exception(
				"[" . $class . ", '" . $method . "'] is not a valid callable."
			);

		}

		private function wrapClosure(Closure $closure ) : Closure {

			return function () use ( $closure ) {

				$args = func_get_args();

				return $this->container->call($closure, ...$args);

			};


		}

		private function wrapController (array $controller ) : Closure {

			 return function () use ( $controller ) {

				$args = func_get_args();

				return $this->container->call( implode('@', $controller), ...$args);

			};

		}

	}