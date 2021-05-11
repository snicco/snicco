<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use WPEmerge\Routing\Conditions\CustomCondition;
	use WPEmerge\Support\Str;
	use WPEmerge\Traits\ReflectsCallable;

	class ConditionBlueprint {

		use ReflectsCallable;

		const NEGATES_SIGN = '!';
		const NEGATES_WORD = 'negate';


		/** @var string */
		private $type;

		/**
		 * @var array
		 */
		private $args;

		/**
		 * @var object|null
		 */
		private $instance;

		/**
		 * @var string
		 */
		private $negates;

		public function __construct( array $arguments ) {

			[ $type, $args ] = $this->parseTypeAndArgs( $arguments );

			$this->type     = $type;
			$this->args     = $args;
			$this->instance = $this->parseInstance($arguments);


		}

		public function args() : array {

			return $this->args;

		}

		public function type() : string {

			return $this->type;

		}


		public function instance () : ?object {

			return $this->instance;

		}

		public function negates () : ?string {

			return $this->negates;

		}

		private function parseTypeAndArgs( array $args ) : array {

			$copy = $args;

			$type = array_shift( $copy );

			$type = $this->getClass( $type ) ?? $type;

			if ( Str::contains( $type, self::NEGATES_SIGN ) ) {

				$this->negates = Str::after( $type, self::NEGATES_SIGN );

				$type = self::NEGATES_WORD;

				return [ $type, $copy ];

			}

			if ( $type === self::NEGATES_WORD ) {

				$this->negates = array_shift( $copy );

				return [ $type, $copy  ];

			}

			if ( is_callable( $type ) ) {

				return [ CustomCondition::class, $copy ];

			}

			return [ $type, $copy  ];

		}

		private function parseInstance( array $original_args ) : ?object {

			$candidate = ($original_args[0] == self::NEGATES_WORD ) ? $original_args[1] : $original_args[0];


			if ( is_callable($candidate) ) {

				return new CustomCondition($candidate, ...$this->args);

			}

			return ( is_object( $candidate ) ) ? $candidate : null;

		}



	}
