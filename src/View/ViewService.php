<?php


	namespace WPEmerge\View;

	use Closure;
	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Helpers\MixedType;

	/**
	 * Provide general view-related functionality.
	 */
	class ViewService implements ViewFinderInterface {

		/**
		 * Configuration.
		 *
		 * @var array<string, mixed>
		 */
		private $config = [];

		/**
		 * View engine.
		 *
		 * @var ViewEngineInterface
		 */
		private $engine = null;

		/**
		 * Global variables.
		 *
		 * @var array
		 */
		private $globals = [];

		/**
		 * View composers.
		 *
		 * @var array
		 */
		private $composers = [];


		public function __construct( array $config, ViewEngineInterface $engine ) {

			$this->config = $config;
			$this->engine = $engine;
		}


		public function getGlobals() : array {

			return $this->globals;
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
		 * Get view composer.
		 *
		 * @param  string  $view
		 *
		 * @return Handler[]
		 */
		public function getComposersForView( $view ) {

			$view = $this->engine->canonical( $view );

			$composers = [];

			foreach ( $this->composers as $composer ) {
				if ( in_array( $view, $composer['views'], true ) ) {
					$composers[] = $composer['composer'];
				}
			}

			return $composers;
		}

		/**
		 * Add view composer.
		 *
		 * @param  string|string[]  $views
		 * @param  string|Closure  $composer
		 *
		 * @return void
		 */
		public function addComposer( $views, $composer ) {

			$views = array_map( function ( $view ) {

				return $this->engine->canonical( $view );

			}, MixedType::toArray( $views ) );

			$handler = $this->handler_factory->make( $composer, 'compose', $this->config['namespace'] );

			$this->composers[] = [
				'views'    => $views,
				'composer' => $handler,
			];
		}

		/**
		 * Composes a view instance with contexts in the following order: Global, Composers, Local.
		 *
		 * @param  ViewInterface  $view
		 *
		 * @return void
		 */
		public function compose( ViewInterface $view ) {

			$global = [ 'global' => $this->getGlobals() ];
			$view->with( $global );

			$composers = $this->getComposersForView( $view->getName() );
			foreach ( $composers as $composer ) {
				$composer->execute( $view );
			}

			$view->with( $view->getContext() );
		}

		/**
		 * Create a view instance.
		 *
		 * @param  string|string[]  $views
		 *
		 * @return ViewInterface
		 */
		public function make( $views ) {

			return $this->engine->make( MixedType::toArray( $views ) );

		}

		/**
		 * Trigger core hooks for a partial, if any.
		 *
		 * @codeCoverageIgnore
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
		 * @codeCoverageIgnore
		 *
		 * @param  string|string[]  $views
		 * @param  array<string, mixed>  $context
		 *
		 * @return void
		 */
		public function render( $views, $context = [] ) {

			$view = $this->make( $views )->with( $context );
			$this->triggerPartialHooks( $view->getName() );
			echo $view->toString();
		}

		public function exists( $view ) {

			return $this->engine->exists( $view );

		}

		public function canonical( $view ) {

			return $this->engine->canonical( $view );

		}

	}
