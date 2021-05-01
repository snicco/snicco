<?php


	namespace WPEmerge\Routing;

	use Closure;
	use Contracts\ContainerAdapter;
	use Exception;
	use Illuminate\Support\Collection;
	use ReflectionClass;
	use Throwable;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Routing\Conditions\CustomCondition;
	use WPEmerge\Routing\Conditions\UrlCondition;
	use WPEmerge\Routing\Route;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\Str;
	use WPEmerge\Traits\ReflectsCallable;

	use function collect;

	class ConditionFactory {

		use ReflectsCallable;

		const NEGATE_SIGN = '!';
		const NEGATE_WORD = 'negate';

		/**
		 * Registered condition types.
		 *
		 * @var array<string, string>
		 */
		protected $condition_types = [];

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;


		public function __construct( array $condition_types, ContainerAdapter $container ) {

			$this->condition_types = $condition_types;
			$this->container       = $container;
		}

		public function compileConditions( Route $route ) : array {

			$conditions = collect( $route->getConditions() );

			$conditions = $conditions
				->map( function ( $condition ) {

					if ( $compiled = $this->alreadyCompiled( $condition ) ) {

						return $compiled;

					}

					return $this->makeNew( $condition );

				} )
				->unique();

			return $conditions->all();

		}


		/**
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 * @throws \Exception
		 */
		private function makeNew( array $options ) {

			$condition_options = $this->parseConditionOptions( $options );
			$condition_class   = $this->getConditionTypeClass( $condition_options['type'] );

			try {

				$reflection = new ReflectionClass( $condition_class );

				return $reflection->newInstanceArgs( $condition_options['arguments'] ?? [] );


			}

			catch ( Throwable $e ) {


				try {

					$args = $this->buildNamedConstructorArgs( $condition_class, $condition_options['arguments'] );

					return $this->container->make( $condition_class, $args );

				}

				catch ( Throwable $e ) {


					throw new ConfigurationException( 'Error while creating the RouteCondition: ' . $condition_class . PHP_EOL . $e->getMessage() );


				}


			}


		}

		/**
		 * @throws ConfigurationException
		 */
		private function getConditionTypeClass( string $condition_type ) : string {

			if ( ! isset( $this->condition_types[ $condition_type ] ) ) {

				throw new ConfigurationException( 'Trying to create unknown condition: ' . $condition_type );

			}

			return $this->condition_types[ $condition_type ];
		}

		private function isNegatedCondition( string $condition ) : bool {

			if ( Str::contains( $condition, self::NEGATE_SIGN ) ) {

				return true;

			}

			if ( Str::contains( $condition, self::NEGATE_WORD ) ) {

				return true;

			}

			return false;
		}

		private function parseNegatedCondition( string $type, array $arguments ) : array {


			if ( Arr::firstEl( $arguments ) instanceof ConditionInterface ) {

				return [
					'type'      => self::NEGATE_WORD,
					'arguments' => $this->negatedObjectCondition( Arr::first( $arguments ) ),
				];

			}

			if ( Arr::firstEl( $arguments ) instanceof Closure ) {

				return [
					'type'      => self::NEGATE_WORD,
					'arguments' => $this->negatedCustomCondition( $arguments ),
				];

			}

			return $this->negatedStringCondition( $type, $arguments );


		}

		private function negatedObjectCondition( ConditionInterface $condition ) : array {

			return [ $condition ];

		}

		private function negatedCustomCondition( array $arguments ) : array {

			$condition = new CustomCondition(
				Arr::firstEl( $arguments ),
				...Arr::allAfter( $arguments, 1 )
			);

			return [ $condition ];

		}

		private function negatedStringCondition( string $type, array $arguments ) : array {

			$type = $this->negates( $type );

			$condition_type = ( $type === 'negate' ) ? $arguments[0] : $type;

			return [
				'type'      => self::NEGATE_WORD,
				'arguments' => [ $this->makeNew( array_merge( [ $condition_type ], $arguments ) ) ],
			];


		}

		private function negates( string $type ) : string {

			if ( Str::contains( $type, self::NEGATE_SIGN ) ) {

				return Str::after( $type, self::NEGATE_SIGN );

			}

			return $type;

		}

		private function parseConditionOptions( array $options ) : array {

			$type      = $options[0];
			$arguments = array_values( array_slice( $options, 1 ) );

			if ( is_callable( $type ) ) {

				return $this->newCustomCondition($options);

			}

			if ( $this->isNegatedCondition( $type ) ) {

				return $this->parseNegatedCondition( $type, $arguments );

			}

			if ( ! $this->isConditionAlias( $type ) ) {

				throw new ConfigurationException( 'Unknown condition type specified: ' . $type );

			}

			return [ 'type' => $type, 'arguments' => $arguments ];


		}

		private function newCustomCondition( array $options ) : array {

			return [ 'type' => 'custom', 'arguments' => $options ];

		}

		private function alreadyCompiled( array $condition ) : ?ConditionInterface {

			$condition = Arr::firstEl( $condition );

			return ( $condition instanceof ConditionInterface ) ? $condition : null;

		}

		private function isConditionAlias( string $condition ) : bool {


			$condition = Str::after( $condition, self::NEGATE_SIGN );

			return isset( $this->condition_types[ $condition ] );
		}


	}
