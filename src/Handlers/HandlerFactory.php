<?php


	namespace WPEmerge\Handlers;

	use Closure;
	use Contracts\ContainerAdapter;
	use Exception;
	use WPEmerge\Contracts\RouteHandler;
	use Illuminate\Support\Reflector;
	use WPEmerge\MiddlewareResolver;
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

		public function __construct( array $controller_namespaces, ContainerAdapter $container ) {

			$this->controller_namespaces = $controller_namespaces;
			$this->container             = $container;

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

				return new ClosureHandler( $handler[0], $this->wrapClosure( $handler[0] ) );

			}

			if ( $namespaced_handler = $this->checkIsCallable( $handler ) ) {

				return new Controller(
					$namespaced_handler,
					$this->wrapController( $namespaced_handler ),
					new MiddlewareResolver($this->container)
				);

			}

			$this->fail( $handler[0], $handler[1] );


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

		private function checkIsCallable( array $handler ) : ?array {

			if ( Reflector::isCallable( $handler ) ) {

				return $handler;

			}

			[ $class, $method ] = $handler;

			$matched = collect( $this->controller_namespaces )
				->map( function ( $namespace ) use ( $class, $method ) {

					if ( Reflector::isCallable( [ $namespace . '\\' . $class, $method ] ) ) {

						return [ $namespace . '\\' . $class , $method ] ;

					}

				} )
				->filter( function ( $value ) {

					return $value !== null;

				} );

			return $matched->isNotEmpty() ? $matched->first()  : null ;


		}

		private function fail( $class, $method ) {

			$method = Str::replaceFirst( '@', '', $method );

			throw new Exception(
				"[" . $class . ", '" . $method . "'] is not a valid callable."
			);

		}

		private function wrapClosure( Closure $closure ) : Closure {

			return function ( $args ) use ( $closure ) {

				return $this->container->call( $closure, $args );

			};


		}

		private function wrapController( array $controller ) : Closure {

			return function ( $args ) use ( $controller ) {

				return $this->container->call( implode( '@', $controller ), $args );

			};

		}

	}