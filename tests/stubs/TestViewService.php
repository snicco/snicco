<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Contracts\ViewServiceInterface;

	class TestViewService implements ViewServiceInterface {

		public function compose( ViewInterface $view ) {
			//
		}

		public function make( $views ) : ViewInterface {
			//
		}

		public function render( $views, array $context = [] ) : string {
			//
		}

	}