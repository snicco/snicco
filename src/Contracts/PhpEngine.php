<?php


	declare( strict_types = 1 );


	namespace BetterWP\Contracts;


	interface PhpEngine extends ViewEngineInterface {

		/**
		 * Pop the top-most layout content from the stack, render and return it.
		 */
		public function includeNextView() :void;

		public function renderPhpView(PhpViewInterface $view) :string;

	}