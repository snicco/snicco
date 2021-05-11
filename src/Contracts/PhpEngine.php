<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	use WPEmerge\View\PhpView;

	interface PhpEngine extends ViewEngineInterface {

		/**
		 * Pop the top-most layout content from the stack, render and return it.
		 */
		public function includeChildViews() :void;

		public function renderPhpView(PhpView $view) :string;

	}