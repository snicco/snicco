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
		public function getContext( string $key = null, $default = null );

		/**
		 * Add context values.
		 *
		 * @param  string|array<string, mixed>  $key
		 * @param  mixed  $value
		 *
		 * @return \WPEmerge\Contracts\ViewInterface
		 */
		public function with( $key, $value = null ) :ViewInterface;

	}
