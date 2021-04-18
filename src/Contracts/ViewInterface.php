<?php


	namespace WPEmerge\Contracts;


	/**
	 * Represent and render a view to a string.
	 */
	interface ViewInterface extends HasContextInterface, ResponsableInterface {

		/**
		 * Get name.
		 *
		 * @return string
		 */
		public function getName();

		/**
		 * Set name.
		 *
		 * @param  string  $name
		 *
		 * @return static $this
		 */
		public function setName( $name );

		/**
		 * Render the view to a string.
		 *
		 * @return string
		 */
		public function toString();

	}
