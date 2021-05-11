<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	/**
	 * Represent an object which has an array of attributes.
	 */
	interface HasAttributesInterface {

		/**
		 * Get attribute.
		 *
		 * @param  string  $attribute
		 * @param  mixed  $default
		 *
		 * @return mixed
		 */
		public function getAttribute( $attribute, $default = '' );

		/**
		 * Get all attributes.
		 *
		 * @return array<string, mixed>
		 */
		public function getAttributes();

		/**
		 * Set attribute.
		 *
		 * @param  string  $attribute
		 * @param  mixed  $value
		 *
		 * @return void
		 */
		public function setAttribute( $attribute, $value );

		/**
		 * Fluent alias for setAttribute().
		 *
		 * @param  string  $attribute
		 * @param  mixed  $value
		 *
		 * @return static $this
		 */
		public function attribute( $attribute, $value );

		/**
		 * Set attributes.
		 * No attempt to merge attributes is done - this is a direct overwrite operation.
		 *
		 * @param  array<string, mixed>  $attributes
		 *
		 * @return void
		 */
		public function setAttributes( $attributes );

		/**
		 * Fluent alias for setAttributes().
		 *
		 * @param  array<string, mixed>  $attributes
		 *
		 * @return static               $this
		 */
		public function attributes( $attributes );

	}
