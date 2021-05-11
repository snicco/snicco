<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	interface HasContextInterface {

		/**
		 * Get context values.
		 *
		 * @param  string|null  $key
		 * @param  mixed|null  $default
		 *
		 * @return mixed
		 */
		public function getContext( $key = null, $default = null );

		/**
		 * Add context values.
		 *
		 * @param  string|array<string, mixed>  $key
		 * @param  mixed  $value
		 *
		 * @return static                      $this
		 */
		public function with( $key, $value = null );

	}
