<?php


	declare( strict_types = 1 );


	namespace Snicco\View;

	use BetterWpHooks\Exceptions\ConfigurationException;
    use Snicco\Contracts\PhpEngine;
    use Snicco\Contracts\PhpViewInterface;
    use Snicco\Contracts\ViewInterface;
    use Snicco\Events\MakingView;
    use Snicco\ExceptionHandling\Exceptions\ViewException;
    use Snicco\ExceptionHandling\Exceptions\ViewNotFoundException;
    use Snicco\Support\Arr;
    use Throwable;

    class PhpViewEngine implements PhpEngine {

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



		public function __construct( PhpViewFinder $finder ) {

			$this->finder  = $finder;

		}

		/**
		 * @throws ConfigurationException
		 */
		public function includeNextView() : void {

			if ( ! $view = $this->getNextViewFromStack() ) {

				return;

			}

			$clone = clone $view;

			MakingView::dispatch([$clone]);

			$this->finder->includeFile(
				$clone->path(),
				$clone->context()
			);

		}

		public function make( $views ) : ViewInterface {

			$views = Arr::wrap($views);

			$view_name = collect( $views )
				->reject( function ( string $view_name ) {

					return ! $this->finder->exists( $view_name );

				} )
				->whenEmpty( function () use ( $views ) {

					throw new ViewNotFoundException( 'Views not found. Tried [' . implode( ', ', $views ) . ']' );


				} )
				->first();


			return new PhpView($this, $view_name, $this->finder->filePath($view_name));

		}

		public function renderPhpView( PhpViewInterface $view ) : string {


			$ob_level = ob_get_level();

			ob_start();

			try {

				$this->requirePhpView($view);

			}
			catch ( Throwable $e ) {

				$this->handleViewException($e, $ob_level, $view );

			}

			$html = ob_get_clean();

			return $html;

		}

		private function addToViewStack( PhpViewInterface $view ) : void {

			$this->view_stack[] = $view;
		}

		private function getNextViewFromStack() : ?PhpViewInterface {

			return array_pop( $this->view_stack );
		}

		private function handleViewException( Throwable $e , $ob_level, PhpViewInterface $view) {

			while (ob_get_level() > $ob_level) {
				ob_end_clean();
			}

			throw new ViewException(
				'Error rendering view: [' . $view->name() . '].', $e
			);

		}

		private function requirePhpView(PhpViewInterface $view) {

			$this->addToViewStack($view);

			if ( $view->parent() !== null ) {

				$this->requirePhpView($view->parent());

			}

			$this->includeNextView();

		}

	}
