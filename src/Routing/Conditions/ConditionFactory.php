<?php


	namespace WPEmerge\Routing\Conditions;

	use Closure;
	use Contracts\ContainerAdapter;
	use ReflectionClass;
	use Throwable;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\Str;
	use WPEmerge\Traits\ReflectsCallable;

	/**
	 * Check against the current url
	 */
	class ConditionFactory {

		use ReflectsCallable;

		const NEGATE_CONDITION_SIGN = '!';
		const NEGATE_CONDITION_WORD = 'negate';

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

		/**
		 * Constructor.
		 *
		 * @codeCoverageIgnore
		 *
		 * @param  array<string, string>  $condition_types
		 */
		public function __construct( $condition_types, ContainerAdapter $container ) {

			$this->condition_types = $condition_types;
			$this->container       = $container;
		}

		/**
		 * Create a new condition.
		 *
		 * @param  string|array|Closure  $options
		 *
		 * @return ConditionInterface
		 */
		public function make( $options ) {

			if ( is_string( $options ) ) {
				return $this->makeFromUrl( $options );
			}

			if ( is_array( $options ) ) {
				return $this->makeFromArray( $options );
			}

			if ( $options instanceof Closure ) {
				return $this->makeFromClosure( $options );
			}

			throw new ConfigurationException( 'Invalid condition options supplied.' );
		}

		/**
		 * Ensure value is a condition.
		 *
		 * @param  string|array|Closure|ConditionInterface  $value
		 *
		 * @return ConditionInterface
		 */
		public function condition( $value ) {

			if ( $value instanceof ConditionInterface ) {
				return $value;
			}

			return $this->make( $value );
		}

		/**
		 * Get condition class for condition type.
		 *
		 * @param  string  $condition_type
		 *
		 * @return string|null
		 */
		protected function getConditionTypeClass( $condition_type ) {

			if ( ! isset( $this->condition_types[ $condition_type ] ) ) {
				return null;
			}

			return $this->condition_types[ $condition_type ];
		}

		/**
		 * Check if the passed argument is a registered condition type.
		 *
		 * @param  mixed  $condition_type
		 *
		 * @return boolean
		 */
		protected function conditionTypeRegistered( $condition_type ) {

			if ( ! is_string( $condition_type ) ) {
				return false;
			}

			return $this->getConditionTypeClass( $condition_type ) !== null;
		}


		protected function isNegatedCondition( $condition ) : bool {

			if ( is_object( $condition ) ) {

				return false;

			}

			if ( Str::contains( $condition, self::NEGATE_CONDITION_SIGN ) ) {

				return true;

			}

			if ( Str::contains( $condition, self::NEGATE_CONDITION_WORD ) ) {

				return true;

			}

			return false;
		}

		/**
		 * Parse a negated condition and its arguments.
		 *
		 * @param  string|object  $type
		 * @param  array  $arguments
		 *
		 * @return array
		 */
		protected function parseNegatedCondition( $type, array $arguments ) {


			if ( Arr::firstEl( $arguments ) instanceof ConditionInterface ) {

				return [ 'type' => self::NEGATE_CONDITION_WORD, 'arguments' => [ $arguments[0] ] ];

			}

			if ( Arr::firstEl( $arguments ) instanceof Closure ) {

				$condition = call_user_func( [ $this, 'make' ], $arguments );

				return [ 'type' => self::NEGATE_CONDITION_WORD, 'arguments' => [ $condition ] ];

			}

			if ( Str::contains( $type, self::NEGATE_CONDITION_SIGN ) ) {

				$negated_type = Str::after( $type, self::NEGATE_CONDITION_SIGN );
				$arguments    = array_merge( [ $negated_type ], $arguments );
				$type         = self::NEGATE_CONDITION_WORD;
				$condition    = call_user_func( [ $this, 'make' ], $arguments );

				return [ 'type' => $type, 'arguments' => [ $condition ] ];
			}

			$negated_type = $arguments[0];
			$arguments    = array_merge( [ $negated_type ], $arguments );
			$type         = self::NEGATE_CONDITION_WORD;
			$condition    = call_user_func( [ $this, 'make' ], $arguments );

			return [ 'type' => $type, 'arguments' => [ $condition ] ];


		}

		/**
		 * Parse the condition type and its arguments from an options array.
		 *
		 * @param  array  $options
		 *
		 * @return array
		 */
		protected function parseConditionOptions( $options ) {

			$type      = $options[0];
			$arguments = array_values( array_slice( $options, 1 ) );

			if ( $this->isNegatedCondition( $type ) ) {

				return $this->parseNegatedCondition( $type, $arguments );

			}

			if ( ! $this->conditionTypeRegistered( $type ) ) {
				if ( is_callable( $type ) ) {
					return [ 'type' => 'custom', 'arguments' => $options ];
				}

				throw new ConfigurationException( 'Unknown condition type specified: ' . $type );
			}

			return [ 'type' => $type, 'arguments' => $arguments ];
		}

		/**
		 * Create a new condition from a url.
		 *
		 * @param  string  $url
		 *
		 * @return ConditionInterface
		 */
		protected function makeFromUrl( $url ) {

			return new UrlCondition( $url );
		}

		/**
		 * Create a new condition from an array.
		 *
		 * @param  array  $options
		 *
		 * @return ConditionInterface
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		protected function makeFromArray( $options ) {

			if ( count( $options ) === 0 ) {
				throw new ConfigurationException( 'No condition type specified.' );
			}

			if ( is_array( $options[0] ) ) {

				return $this->makeFromArrayOfConditions( $options );

			}

			$condition_options = $this->parseConditionOptions( $options );
			$condition_class   = $this->getConditionTypeClass( $condition_options['type'] );

			try {

				$reflection = new ReflectionClass( $condition_class );
				/** @var ConditionInterface $instance */
				$instance = $reflection->newInstanceArgs( $condition_options['arguments'] );

				return $instance;

			}

			catch ( Throwable $e ) {

				try {

					return $this->container->make( $condition_class, $condition_options['arguments'] );

				}

				catch ( Throwable $e ) {

					throw new ConfigurationException( 'Error while creating the RouteCondition: ' . $e->getMessage() );

				}


			}


		}

		/**
		 * Create a new condition from an array of conditions.
		 *
		 * @param  array  $options
		 *
		 * @return ConditionInterface
		 */
		protected function makeFromArrayOfConditions( $options ) {

			$conditions = array_map( function ( $condition ) {

				if ( $condition instanceof ConditionInterface ) {
					return $condition;
				}

				return $this->make( $condition );
			}, $options );

			return new MultipleCondition( $conditions );
		}

		/**
		 * Create a new condition from a closure.
		 *
		 * @param  Closure  $closure
		 *
		 * @return ConditionInterface
		 */
		protected function makeFromClosure( Closure $closure ) {

			return new CustomCondition( $closure );
		}

		/**
		 * Merge group condition attribute.
		 *
		 * @param  string|array|Closure|ConditionInterface|null  $old
		 * @param  string|array|Closure|ConditionInterface|null  $new
		 *
		 * @return ConditionInterface|null
		 */
		public function merge( $old, $new ) {

			if ( empty( $old ) ) {

				if ( empty( $new ) ) {

					return null;

				}

				return $this->condition( $new );

			} elseif ( empty( $new ) ) {

				return $this->condition( $old );

			}

			return $this->mergeConditions( $this->condition( $old ), $this->condition( $new ) );
		}

		/**
		 * Merge condition instances.
		 *
		 * @param  ConditionInterface  $old
		 * @param  ConditionInterface  $new
		 *
		 * @return ConditionInterface
		 */
		public function mergeConditions( ConditionInterface $old, ConditionInterface $new ) {

			if ( $old instanceof UrlCondition && $new instanceof UrlCondition ) {

				return $old->concatenate( $new->getUrl(), $new->getUrlWhere() );

			}

			return $this->makeFromArrayOfConditions( [ $old, $new ] );
		}

	}
