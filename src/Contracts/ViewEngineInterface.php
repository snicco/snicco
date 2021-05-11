<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


	/**
	 * Interface that view engines must implement
	 */
	interface ViewEngineInterface extends ViewFinderInterface {

		/**
		 * Create a view instance from the first view name that exists.
		 *
		 * @param  string|string[]  $views
		 *
		 * @return ViewInterface
		 */
		public function make( $views ) : ViewInterface;


	}
