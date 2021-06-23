<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Contracts\ViewFactoryInterface;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\Support\Arr;

	class ViewFactory implements ViewFactoryInterface {

		/**
		 * View engine.
		 *
		 * @var ViewEngineInterface
		 */
		private $engine;

		/**
		 * @var ViewComposerCollection
		 */
		private $composer_collection;

		/**
		 * @var VariableBag
		 */
		private $global_context;

		public function __construct(
			ViewEngineInterface $engine,
			ViewComposerCollection $composer_collection,
			GlobalContext $global_context
		) {
			$this->engine = $engine;
			$this->composer_collection = $composer_collection;
			$this->global_context = $global_context;
		}


		/**
		 * Composes a view instance with contexts in the following order: Global, Composers, Local.
		 *
		 * @param  ViewInterface  $view
		 *
		 * @return void
		 */
		public function compose( ViewInterface $view ) {

			$local_context = $view->context();

            foreach ($this->global_context->get() as $name => $context) {

                $context = is_callable($context)
                    ? call_user_func($context)
                    : $context;

                $view->with( $name, $context );

            }

			$this->composer_collection->executeUsing($view);

			$view->with( $local_context );

		}

		/**
		 * Create a view instance.
		 *
		 * @param  string|string[]  $views
		 *
		 * @return ViewInterface
		 */
		public function make( $views ) : ViewInterface {

			return $this->engine->make( Arr::wrap($views) );

		}

		/**
		 * Compile a view to a string.
		 *
		 * @param  string|string[]  $views
		 * @param  array<string, mixed>  $context
		 *
		 * @return string
		 */
		public function render( $views, array $context = [] ) : string {

			$view = $this->make( $views )->with( $context );

			return $view->toString();

		}

		public function pathForView ( string $view_name ) :string {

		    $view = $this->make($view_name);

		    return $view->path();

        }

        public function includeChild () {

		    if ( ! $this->engine instanceof PhpViewEngine ) {
		        return;
            }

		    return $this->engine->includeNextView();

        }

	}
