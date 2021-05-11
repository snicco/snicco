<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Factories;

	use BetterWpHooks\Contracts\Dispatcher;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingAjaxRequest;


	use function get_plugin_page_hook;
	use function wp_doing_ajax;

	class DynamicHooksFactory {


		/**
		 * @var \BetterWpHooks\Dispatchers\WordpressDispatcher
		 */
		private $dispatcher;


		public function __construct( Dispatcher $dispatcher ) {

			$this->dispatcher = $dispatcher;

		}

		public function create( RequestInterface $request ) {

			if ( wp_doing_ajax() ) {

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

		private function createAjaxHooks( RequestInterface $request ) {

			$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : '';

			$this->dispatcher->listen( 'wp_ajax_' . $action, function () use ( $request ) {

				IncomingAjaxRequest::dispatch([$request]);

			} );

			$this->dispatcher->listen( 'wp_ajax_nopriv_' . $action, function () use ( $request ) {

				IncomingAjaxRequest::dispatch([$request]);

			} );



		}

		private function createAdminHooks(RequestInterface $request) {

			if ( $hook = $this->getAdminPageHook() ) {

				$this->dispatcher->listen( 'load-' . $hook, function () use ( $request ) {

					IncomingAdminRequest::dispatch([$request]);

				} );

				$this->dispatcher->listen( $hook, function () {

					AdminBodySendable::dispatch();

				} );

			}

		}

	}