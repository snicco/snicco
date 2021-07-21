<?php


	declare( strict_types = 1 );


	namespace Snicco\Contracts;


	use Snicco\ExceptionHandling\Exceptions\ViewException;
    use Snicco\ExceptionHandling\Exceptions\ViewNotFoundException;

    interface ViewFactoryInterface {

		/**
		 * Composes a view instance with contexts in the following order: Global, Composers, Local.
		 *
		 * @param  ViewInterface  $view
		 *
		 * @return void
		 */
		public function compose( ViewInterface $view );

		/**
		 * Create a view instance.
		 *
		 * @param  string|string[]  $views
		 *
		 * @return ViewInterface
         * @throws ViewException|ViewNotFoundException
		 */
		public function make( $views ) : ViewInterface;


		/**
		 * Compile a view to a string.
		 *
		 * @param  string|string[]  $views
		 * @param  array<string, mixed>  $context
		 *
		 * @return string
		 */
		public function render( $views, array $context = [] ) : string;

	}