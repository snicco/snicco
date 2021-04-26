<?php


	namespace WPEmerge\Contracts;


	/**
	 * Interface that view engines must implement
	 */
	interface ViewEngineInterface extends ViewFinderInterface {

		/**
		 * Create a view instance from the first view name that exists.
		 *
		 * @param  string[]  $views
		 *
		 * @return ViewInterface
		 */
		public function make( $views );


	}
