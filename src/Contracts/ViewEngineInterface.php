<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


	interface ViewEngineInterface  {

		/**
		 * Create a view instance from the first view name that exists.
		 *
		 * @param  string|string[]  $views
		 *
		 * @return ViewInterface
		 */
		public function make( $views ) : ViewInterface;

		/**
		 * Pop the top-most layout content from the stack, render and return it.
		 */
		public function includeChildViews();

	}
