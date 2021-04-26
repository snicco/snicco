<?php


	namespace WPEmerge\View;

	use Closure;
	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Helpers\MixedType;
	use WPEmerge\ViewComposers\ViewComposerCollection;


	class ViewService implements ViewFinderInterface {


		/**
		 * View engine.
		 *
		 * @var ViewEngineInterface
		 */
		private $engine;

		/**
		 * Global variables.
		 *
		 * @var array
		 */
		private $globals = [];

		/**
		 * @var \WPEmerge\ViewComposers\ViewComposerCollection
		 */
		private $composer_collection;


		public function __construct( ViewEngineInterface $engine, ViewComposerCollection $composer_collection) {

			$this->engine = $engine;
			$this->composer_collection = $composer_collection;
		}

		public function addGlobal( string $key, $value ) :void {

			$this->globals[ $key ] = $value;
		}

		public function addGlobals( array $globals ) :void {

			foreach ( $globals as $key => $value ) {
				$this->addGlobal( $key, $value );
			}
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
			$global_context = [ 'global' => $this->globals ];

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

			return $this->engine->make( MixedType::toArray( $views ) );

		}

		/**
		 * Trigger core hooks for a partial, if any.
		 *
		 *
		 * @param  string  $name
		 *
		 * @return void
		 */
		public function triggerPartialHooks( $name ) {

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

		/**
		 * Render a view.
		 *
		 * @param  string|string[]  $views
		 * @param  array<string, mixed>  $context
		 *
		 * @return string
		 */
		public function render( $views, $context = [] ) {

			$view = $this->make( $views )->with( $context );

			$this->triggerPartialHooks( $view->getName() );

			return $view->toString();

		}

		public function exists( $view ) : bool {

			return $this->engine->exists( $view );

		}

		public function canonical( $view ) : string {

			return $this->engine->canonical( $view );

		}

	}
