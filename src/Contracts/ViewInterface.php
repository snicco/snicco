<?php


	declare( strict_types = 1 );


	namespace BetterWP\Contracts;


	interface ViewInterface extends ResponsableInterface {

		/**
		 * Render the view to a string.
		 *
		 * @return string
		 */
		public function toString() :string;


		/**
		 * Add context values.
		 *
		 * @param  string|array<string, mixed>  $key
		 * @param  mixed  $value
		 *
		 * @return ViewInterface
		 */
		public function with( $key, $value = null ) :ViewInterface;

        /**
         * Get context values.
         *
         * @param  string|null  $key
         * @param  mixed|null  $default
         *
         * @return mixed
         */
        public function context( string $key = null, $default = null );

        public function name();

        public function path();

	}
