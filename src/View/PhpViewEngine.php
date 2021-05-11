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
		 * Name of view file header based on which to resolve parent views.
		 *
		 * @var string
		 */
		public const PARENT_FILE_INDICATOR = 'Layout';

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

		public function includeChildViews() : void {

			if ( ! $view = $this->getNextViewFromStack() ) {

				return;

			}

			$clone = clone $view;

			call_user_func( $this->compose, $clone );

			$this->finder->includeFile(
				$clone->getFilepath(),
				$clone->getContext()
			);

		}

		public function make( $views ) : ViewInterface {

			$view = collect( $views )
				->reject( function ( string $view_name ) {

					return ! $this->finder->exists( $view_name );

				} )
				->whenEmpty( function () use ( $views ) {

					throw new ViewNotFoundException( 'View not found for [' . implode( ', ', $views ) . ']' );


				} )
				->first();

			return $this->makePhpView( $view, $this->finder->filePath( $view ) );


		}

		/**
		 * Create a view instance.
		 *
		 * @param  string  $name
		 * @param  string  $filepath
		 *
		 * @return \WPEmerge\View\PhpView
		 * @throws ViewNotFoundException
		 */
		private function makePhpView( string $name, string $filepath ) : PhpView {

			$view = new PhpView( $this, $name, $filepath );

			$parent_view = $this->getParentView( $view );

			if ( $parent_view !== null ) {
				$view->withParentView( $parent_view );
			}

			return $view;
		}

		/**
		 * Create a view instance for the given view's layout header, if any.
		 *
		 * @param  PhpView  $view
		 *
		 * @return \WPEmerge\View\PhpView|null
		 * @throws ViewNotFoundException
		 */
		private function getParentView( PhpView $view ) : ?PhpView {

			if ( empty( $layout_headers = $this->parseFileHeaders( $view ) ) ) {
				return null;
			}

			$layout_file = trim( $layout_headers[0] );

			if ( ! $this->finder->exists( $layout_file ) ) {

				throw new ViewNotFoundException(
					'View layout not found for [' . $layout_file . ']'
				);
			}

			return $this->makeParentView($layout_file);

		}

		private function makeParentView(string $parent_file_name ) : PhpView {

			return $this->makePhpView( $parent_file_name, $this->finder->filePath( $parent_file_name ) );


		}

		private function addToViewStack( PhpView $view ) : void {

			$this->view_stack[] = $view;
		}

		private function getNextViewFromStack() : ?PhpView {

			return array_pop( $this->view_stack );
		}

		private function parseFileHeaders( PhpView $view ) : array {

			return array_filter( get_file_data(
				$view->getFilepath(),
				[ self::PARENT_FILE_INDICATOR ]
			) );
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

		private function handleViewException( Throwable $e , $ob_level, PhpView $view) {

			while (ob_get_level() > $ob_level) {
				ob_end_clean();
			}

			throw new ViewException(
				'Error rendering view: [' . $view->getName() . '].' .
				PHP_EOL . $e->getMessage()
			);

		}

		private function requirePhpView(PhpView $view) {

			$this->addToViewStack($view);

			if ( $view->parent() !== null ) {

				$this->requirePhpView($view->parent());

			}

			$this->includeChildViews();

		}

	}
