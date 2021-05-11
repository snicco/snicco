<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Exceptions\ViewNotFoundException;

	/**
	 * Render view files with php.
	 */
	class PhpViewEngine implements ViewEngineInterface {

		/**
		 * Name of view file header based on which to resolve layouts.
		 *
		 * @var string
		 */
		private $layout_file_header = 'Layout';

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
		private $layout_content_stack = [];


		public function __construct( callable $compose, PhpViewFinder $finder ) {

			$this->compose = $compose;
			$this->finder  = $finder;

		}

		public function exists( string $view_name ) :bool {

			return $this->finder->exists( $view_name );
		}

		public function filePath( string $view_name ) :string  {

			return $this->finder->filePath( $view_name );
		}

		public function make( $views ) :ViewInterface{

			foreach ( $views as $view ) {

				if ( $this->exists( $view ) ) {

					$filepath = $this->finder->filePath( $view );

					return $this->makeView( $view, $filepath );
				}
			}

			throw new ViewNotFoundException( 'View not found for "' . implode( ', ', $views ) . '"' );
		}

		/**
		 * Pop the top-most layout content from the stack, render and return it.
		 *
		 * @return string
		 */
		public function getLayoutContent() :string {

			$view = $this->popLayoutContent();

			if ( ! $view ) {
				return '';
			}

			$clone = clone $view;

			call_user_func( $this->compose, $clone );

			return $this->renderView( $clone );
		}


		/**
		 * Push layout content to the top of the stack.
		 *
		 *
		 * @param  PhpView  $view
		 *
		 * @return void
		 */
		public function pushLayoutContent( PhpView $view ) :void {

			$this->layout_content_stack[] = $view;
		}



		/**
		 * Create a view instance.
		 *
		 * @param  string  $name
		 * @param  string  $filepath
		 *
		 * @return ViewInterface
		 * @throws ViewNotFoundException
		 */
		private function makeView( string $name, string $filepath ) : ViewInterface {

			$view = ( new PhpView( $this ) )
				->setName( $name )
				->setFilepath( $filepath );

			$layout = $this->getViewLayout( $view );

			if ( $layout !== null ) {
				$view->setLayout( $layout );
			}

			return $view;
		}

		/**
		 * Create a view instance for the given view's layout header, if any.
		 *
		 * @param  PhpView  $view
		 *
		 * @return ViewInterface|null
		 * @throws ViewNotFoundException
		 */
		private function getViewLayout( PhpView $view ) : ?ViewInterface {

			$layout_headers = array_filter( get_file_data(
				$view->getFilepath(),
				[ $this->layout_file_header ]
			) );

			if ( empty( $layout_headers ) ) {
				return null;
			}

			$layout_file = trim( $layout_headers[0] );

			if ( ! $this->exists( $layout_file ) ) {
				throw new ViewNotFoundException( 'View layout not found for "' . $layout_file . '"' );
			}

			return $this->makeView( $this->filePath( $layout_file ), $this->finder->filePath( $layout_file ) );
		}

		/**
		 * Render a view.
		 *
		 * @param  PhpView  $__view
		 *
		 * @return string
		 */
		private function renderView( PhpView $__view ) : string {

			$__context = $__view->getContext();
			ob_start();
			extract( $__context, EXTR_OVERWRITE );

			include $__view->getFilepath();

			return ob_get_clean();
		}


		/**
		 * Pop the top-most layout content from the stack.
		 *
		 * @return PhpView|null
		 */
		private function popLayoutContent() : ?PhpView {

			return array_pop( $this->layout_content_stack );
		}


	}
