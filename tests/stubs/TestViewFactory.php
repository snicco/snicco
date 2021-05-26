<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Contracts\ViewFactoryInterface;

    class TestViewFactory implements ViewFactoryInterface {

		public function compose( ViewInterface $view ) {
			//
		}

		public function make( $views ) : ViewInterface {

            return new TestView($views);

		}

		public function render( $views, array $context = [] ) : string {
			//
		}

	}