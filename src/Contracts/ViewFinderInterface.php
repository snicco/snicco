<?php


	namespace WPEmerge\Contracts;

	/**
	 * Interface that view finders must implement.
	 */
	interface ViewFinderInterface {

		/**
		 * Check if a view exists.
		 *
		 * @param  string  $view
		 *
		 * @return boolean
		 */
		public function exists( $view );

		/**
		 * Return a canonical string representation of the view name.
		 *
		 * @param  string  $view
		 *
		 * @return string
		 */
		public function canonical( $view );

	}
