<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\Support\Path;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\Support\Arr;
	use WPEmerge\ViewComposers\ViewComposerCollection;


	class ViewService implements ViewServiceInterface {


		/**
		 * View engine.
		 *
		 * @var ViewEngineInterface
		 */
		private $engine;

		/**
		 * @var \WPEmerge\ViewComposers\ViewComposerCollection
		 */
		private $composer_collection;

		/**
		 * @var \WPEmerge\Support\VariableBag
		 */
		private $global_var_bag;


		public function __construct(
			ViewEngineInterface $engine,
			ViewComposerCollection $composer_collection,
			VariableBag $global_var_bag
		) {

			$this->engine = $engine;
			$this->composer_collection = $composer_collection;
			$this->global_var_bag = $global_var_bag;
		}


		/**
		 * Composes a view instance with contexts in the following order: Global, Composers, Local.
		 *
		 * @param  ViewInterface  $view
		 *
		 * @return void
		 */
		public function compose( ViewInterface $view ) {

			$local_context = $view->getContext();

			$global_context = [
				$this->global_var_bag->getPrefix() => $this->global_var_bag
			];

			$view->with( $global_context );

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

			$this->triggerPartialHooks( $view->getName() );

			return $view->toString();

		}


		/**
		 * Trigger core hooks for a partial, if any.
		 *
		 *
		 * @param  string  $name
		 *
		 * @return void
		 */
		private function triggerPartialHooks( string $name ) {

			if ( ! function_exists( 'apply_filters' ) ) {
				// We are not in a WordPress environment - skip triggering hooks.
				return;
			}

			$core_partial = '/^(header|sidebar|footer)(?:-(.*?))?(\.|$)/i';
			$matches      = [];
			$is_partial   = preg_match( $core_partial, $name, $matches );

			if ( $is_partial && apply_filters( "wpemerge.partials.{$matches[1]}.hook", true ) ) {
				do_action( "get_{$matches[1]}", $matches[2] );
			}
		}

	}
