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

			$has_regex = $conditions->filter( [ $this, 'isRegexCondition' ] )->isNotEmpty();

			$conditions = $conditions->when( $has_regex, [ $this, 'filterUrlCondition' ] )
			                         ->map( function ( $condition ) use ( $route ) {

				                         return $this->create( $condition, $route );

			                         } )
			                         ->unique();

			return $conditions->all();

		}


		private function create( array $condition, Route $route ) {

			if ( $this->alreadyCompiled( $compiled = Arr::firstEl( $condition ) ) ) {

				return $compiled;

			}

			if ( $this->isRegexCondition( $condition ) ) {

				return $this->newRegexUrlCondition( $condition, $route );

			}

			return $this->makeNew( $condition );

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

					$args = $this->buildNamedConstructorArgs($condition_class, $condition_options['arguments']);

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

				throw new ConfigurationException('Trying to create unknown condition: ' . $condition_type);

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
					'type' => self::NEGATE_WORD,
					'arguments' => $this->negatedObjectCondition(Arr::first($arguments))
				];

			}

			if ( Arr::firstEl( $arguments ) instanceof Closure ) {

				return [
					'type' => self::NEGATE_WORD,
					'arguments' => $this->negatedCustomCondition($arguments)
				];

			}

			return $this->negatedStringCondition($type, $arguments);


		}

		private function negatedObjectCondition(ConditionInterface $condition ) : array {

			return [ $condition ];

		}

		private function negatedCustomCondition (array $arguments) : array {

			$condition = new CustomCondition(
				Arr::firstEl( $arguments ),
				...Arr::allAfter($arguments, 1 )
			);

			return [$condition];

		}

		private function negatedStringCondition (string $type , array $arguments ) : array {

			$type  = $this->negates($type);

			$condition_type = ( $type === 'negate') ? $arguments[0] : $type;

			return [
				'type' => self::NEGATE_WORD,
				'arguments' => [ $this->makeNew( array_merge( [ $condition_type ], $arguments ) ) ]
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


			if ( is_callable($type) ) {

				return [ 'type' => 'custom', 'arguments' => $options ];

			}

			if ( $this->isNegatedCondition( $type ) ) {

				return $this->parseNegatedCondition( $type, $arguments );

			}


			if ( ! $this->isConditionAlias( $type ) ) {

				throw new ConfigurationException( 'Unknown condition type specified: ' . $type );

			}


			return [ 'type' => $type, 'arguments' => $arguments ];


		}

		private function alreadyCompiled( $condition ) : bool {

			return $condition instanceof ConditionInterface;

		}

		public function isRegexCondition( $condition ) : bool {


			if ( is_object( Arr::firstEl( $condition ) ) ) {

				return false;

			}

			if ( $this->isConditionAlias( Arr::firstEl( $condition ) ) ) {

				return false;

			}

			if ( is_string( Arr::firstEl( $condition ) ) && $this->hasRegexSyntax( Arr::nthEl( $condition, 1 ) ) ) {

				return true;

			}

			return false;

		}

		private function hasRegexSyntax( $string ) : bool {

			return is_string( $string ) && preg_match( '/(^\/.*\/$)/', $string );

		}

		private function newRegexUrlCondition( $condition, Route $route ) : UrlCondition {

			$existing_compiled_url_condition = collect( $route->getConditions() )
				->filter( function ( $condition ) {

					return Arr::firstEl( $condition ) instanceof UrlCondition;

				} )
				->flatten()
				->first();

			if ( ! $existing_compiled_url_condition ) {

				$new_url_condition = new UrlCondition( $route->url() );
				$new_url_condition->setUrl( $new_url_condition );

				return $new_url_condition;

			}

			/** @var $existing_compiled_url_condition \WPEmerge\Routing\Conditions\UrlCondition */
			$existing_compiled_url_condition->setRegex( $this->compileRegex( $condition ) );

			return $existing_compiled_url_condition;


		}

		private function compileRegex( $condition ) {


			if ( is_int( Arr::firstEl( array_keys( $condition ) ) ) ) {

				return Arr::combineFirstTwo( $condition );

			}

			return $condition;


		}

		public function filterUrlCondition( Collection $conditions ) : Collection {

			return $conditions->reject( function ( $condition ) {

				return Arr::firstEl( $condition ) instanceof UrlCondition;

			} );

		}

		private function isConditionAlias( string $condition ) : bool {


			$condition = Str::after( $condition, self::NEGATE_SIGN );

			return isset( $this->condition_types[ $condition ] );
		}



	}
