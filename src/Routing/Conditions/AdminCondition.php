<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\UrlableInterface;


	class AdminCondition implements ConditionInterface, UrlableInterface {

		/**
		 * @var string
		 */
		private $menu;

		/**
		 * @var string
		 */
		private $parent_menu;


		public function __construct( string $menu, string  $parent_menu = '' ) {

			$this->menu        = $menu;
			$this->parent_menu = $parent_menu;
		}


		private function isAdminPage() : bool {

			return is_admin() && ! wp_doing_ajax();

		}


		public function isSatisfied( RequestInterface $request ) : bool {

			if ( ! $this->isAdminPage() ) {
				return false;
			}

			$screen = get_current_screen();

			if ( ! $screen ) {
				return false;
			}


			return $screen->id === get_plugin_page_hookname( $this->menu, $this->parent_menu );
		}


		public function getArguments( RequestInterface $request ) : array {

			return [
				'menu'        => $this->menu,
				'parent_menu' => $this->parent_menu,
			];
		}


		public function toUrl( $arguments = [] ) {

			if ( ! function_exists( 'menu_page_url' ) ) {
				// Attempted to resolve an admin url while not in the admin which can only happen
				// by mistake as admin routes are defined in the admin context only.
				return home_url( '/' );
			}

			return menu_page_url( $this->menu, false );
		}

	}
