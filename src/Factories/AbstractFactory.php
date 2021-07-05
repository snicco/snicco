<?php


	declare( strict_types = 1 );


	namespace BetterWP\Factories;

	use Contracts\ContainerAdapter;
	use Illuminate\Support\Reflector;
	use BetterWP\Contracts\Handler;
	use BetterWP\Support\Str;
	use Closure;
	use BetterWP\ExceptionHandling\Exceptions\Exception;

	use BetterWP\Traits\ReflectsCallable;

	use function collect;

	abstract class AbstractFactory {

		use ReflectsCallable;

		/**
		 * Array of FQN from where we look for the class
		 * being built
		 *
		 * @var array
		 */
		protected $namespaces;

		/**
		 * @var ContainerAdapter
		 */
		protected $container;

		public function __construct( array $namespaces, ContainerAdapter $container ) {

			$this->namespaces = $namespaces;
			$this->container  = $container;

		}

		/**
		 * @param  string|array|callable  $raw_handler
		 *
		 * @return Handler
		 * @throws \Exception
		 */
		abstract public function createUsing($raw_handler) : Handler;

		protected function normalizeInput( $raw_handler ) : array {

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

		protected function checkIsCallable( array $handler ) : ?array {

			if ( Reflector::isCallable( $handler ) ) {

				return $handler;

			}

			if ( count($handler) === 1 && method_exists($handler[0], '__invoke') ) {

			    return [$handler[0], '__invoke'];

            }

			[ $class, $method ] = $handler;

			$matched = collect( $this->namespaces )
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

		protected function fail( $class, $method ) {

			$method = Str::replaceFirst( '@', '', $method );

			throw new Exception(
				"[" . $class . ", '" . $method . "'] is not a valid callable."
			);

		}

		protected function wrapClosure( Closure $closure ) : Closure {

			return function ( $args ) use ( $closure ) {

				return $this->container->call( $closure, $args );

			};


		}

		protected function wrapClass( array $controller ) : Closure {

			return function ( $args ) use ( $controller ) {


				return $this->container->call( implode( '@', $controller ), $args );

			};

		}


	}