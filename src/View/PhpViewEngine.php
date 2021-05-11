<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

	use Throwable;
	use WPEmerge\Contracts\PhpEngine;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Exceptions\ViewException;
	use WPEmerge\Exceptions\ViewNotFoundException;


	class PhpViewEngine implements PhpEngine {

		/**
		 * View compose action.
		 *
		 * @var callable
		 */
		private $compose;

		/**
		 * View finder.
		 *
		 * @var PhpViewFinder
		 */
		private $finder;

		/**
		 * Stack of views ready to be rendered.
		 *
		 * @var PhpView[]
		 */
		private $view_stack = [];


		public function __construct( callable $compose, PhpViewFinder $finder ) {

			$this->compose = $compose;
			$this->finder  = $finder;

		}

		public function includeNextView() : void {

			if ( ! $view = $this->getNextViewFromStack() ) {

				return;

			}

			$clone = clone $view;

			call_user_func( $this->compose, $clone );

			$this->finder->includeFile(
				$clone->path(),
				$clone->context()
			);

		}

		public function make( $views ) : ViewInterface {

			$view_name = collect( $views )
				->reject( function ( string $view_name ) {

					return ! $this->finder->exists( $view_name );

				} )
				->whenEmpty( function () use ( $views ) {

					throw new ViewNotFoundException( 'View not found for [' . implode( ', ', $views ) . ']' );


				} )
				->first();


			return new PhpView($this, $view_name, $this->finder->filePath($view_name));

		}

		public function renderPhpView( PhpView $view ) : string {


			$ob_level = ob_get_level();

			ob_start();

			try {

				$this->requirePhpView($view);

			}
			catch ( Throwable $e ) {

				$this->handleViewException($e, $ob_level, $view );

			}

			return ob_get_clean();

		}

		private function addToViewStack( PhpView $view ) : void {

			$this->view_stack[] = $view;
		}

		private function getNextViewFromStack() : ?PhpView {

			return array_pop( $this->view_stack );
		}

		private function handleViewException( Throwable $e , $ob_level, PhpView $view) {

			while (ob_get_level() > $ob_level) {
				ob_end_clean();
			}

			throw new ViewException(
				'Error rendering view: [' . $view->name() . '].' .
				PHP_EOL . $e->getMessage()
			);

		}

		private function requirePhpView(PhpView $view) {

			$this->addToViewStack($view);

			if ( $view->parent() !== null ) {

				$this->requirePhpView($view->parent());

			}

			$this->includeNextView();

		}

	}
