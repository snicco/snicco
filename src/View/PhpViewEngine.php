<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

	use WPEmerge\Contracts\PhpEngine;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Exceptions\ViewNotFoundException;


	class PhpViewEngine implements PhpEngine {

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

		public function make( $views ) : ViewInterface {


			$view = collect( $views )
				->reject( function ( string $view_name ) {

					return ! $this->exists( $view_name );

				} )
				->whenEmpty( function () use ( $views ) {

					throw new ViewNotFoundException( 'View not found for [' . implode( ', ', $views ) . ']' );


				} )
				->first();

			return $this->makePhpView( $view, $this->filePath( $view ) );


		}

		public function includeChildViews() {

			$view = $this->popLayoutContent();

			if ( ! $view ) {
				return '';
			}

			$clone = clone $view;

			call_user_func( $this->compose, $clone );

			$this->requireView( $clone );


		}

		private function exists( string $view_name ) : bool {

			return $this->finder->exists( $view_name );
		}

		private function filePath( string $view_name ) : string {

			return $this->finder->filePath( $view_name );
		}

		/**
		 * Push layout content to the top of the stack.
		 *
		 *
		 * @param  PhpView  $view
		 *
		 * @return void
		 */
		public function pushLayoutContent( PhpView $view ) : void {

			$this->layout_content_stack[] = $view;
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
		 * @return \WPEmerge\View\PhpView|null
		 * @throws ViewNotFoundException
		 */
		private function getViewLayout( PhpView $view ) : ?PhpView {

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

			return $this->makePhpView(
				$this->filePath( $layout_file ),
				$this->finder->filePath( $layout_file )
			);
		}


		private function requireView( PhpView $__view ) {

			$__context = $__view->getContext();
			extract( $__context, EXTR_OVERWRITE );

			include $__view->getFilepath();

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
