<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Factories;

	use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\OutputBufferRequired;
	use WPEmerge\Events\IncomingAjaxRequest;

    use WPEmerge\Events\RoutesLoadable;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;


	class DynamicHooksFactory {

		/**
		 * @var WordpressDispatcher
		 */
		private $dispatcher;

		public function __construct( Dispatcher $dispatcher ) {

			$this->dispatcher = $dispatcher;

		}

		public function create( Request $request ) {

			if ( WP::isAdminAjax() ) {

				$this->createAjaxHooks($request);

				return;
			}

			$this->createAdminHooks($request);


		}

		/**
		 * Get page hook.
		 * Slightly modified version of code from wp-admin/admin.php.
		 *
		 * @return string|null
		 */
		private function getAdminPageHook() : ?string {

			global $pagenow, $plugin_page;

			if ( $plugin_page ) {

				return get_plugin_page_hook( $plugin_page, $pagenow );

			}

			return null;

		}

		private function createAjaxHooks( Request $request ) {

			$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : '';

			$this->dispatcher->listen( 'wp_ajax_' . $action, function () use ( $request ) {

				RoutesLoadable::dispatch([$request]);

			} );

			$this->dispatcher->listen( 'wp_ajax_nopriv_' . $action, function () use ( $request ) {

				RoutesLoadable::dispatch([$request]);

			} );



		}

		private function createAdminHooks(Request $request) {

			if ( $hook = $this->getAdminPageHook() ) {

                $this->dispatcher->listen( 'load-' . $hook, function () use ( $request ) {

                    OutputBufferRequired::dispatch([$request]);

                });

                $this->dispatcher->listen( $hook, function () use ( $request ) {

                    RoutesLoadable::dispatch([$request]);

                } );

			}

		}

	}