<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


	/**
	 * Represent and render a view to a string.
	 */
	interface ViewInterface extends HasContextInterface, ResponsableInterface {


		/**
		 * Render the view to a string.
		 *
		 * @return string
		 */
		public function toString() :string;

	}
