<?php


	namespace Tests\stubs;

	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Contracts\ViewServiceInterface;

	class TestViewService implements ViewServiceInterface {

		public function compose( ViewInterface $view ) {
			// TODO: Implement compose() method.
		}

		public function make( $views ) : ViewInterface {
			// TODO: Implement make() method.
		}

		public function render( $views, array $context = [] ) : string {
			// TODO: Implement render() method.
		}

	}