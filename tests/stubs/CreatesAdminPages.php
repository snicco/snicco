<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	trait CreatesAdminPages {

		public function urlTo(string $name) {

			add_menu_page($name, $name, 'edit_posts', $name);

			set_current_screen("toplevel_page_{$name}");

			return rtrim(menu_page_url($name, false ), '/');

		}

	}