<?php


	namespace WPEmerge;

	use BetterWpHooks\Contracts\Dispatcher;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;

	class DynamicHooksFactory {

		/**
		 * @var \BetterWpHooks\Dispatchers\WordpressDispatcher
		 */
		private $dispatcher;

		public function __construct( Dispatcher $dispatcher ) {

			$this->dispatcher = $dispatcher;
		}

		public function handleEvent() {

			if ( wp_doing_ajax() ) {

				$this->createAjaxHooks();

				return;
			}

			$this->createAdminHooks();


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

		private function createAjaxHooks() {

		}

		private function createAdminHooks() {

			if ( $hook = $this->getAdminPageHook() ) {

				$this->dispatcher->listen( 'load-' . $hook, function () {

					IncomingAdminRequest::dispatch( [] );

				} );

				$this->dispatcher->listen( $hook, function () {

					AdminBodySendable::dispatch( [] );

				} );

			}

		}

	}