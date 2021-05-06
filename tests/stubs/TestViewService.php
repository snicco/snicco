<?php


	namespace Tests\stubs;

	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Contracts\ViewServiceInterface;

	class TestViewService implements ViewServiceInterface {


		public function compose( ViewInterface $view ) {
			// TODO: Implement compose() method.
		}

		public function make( $views ) : ViewInterface {

			$view = new TestView();
			$view->view = $views;

			return $view;

		}

		public function render( $views, array $context = [] ) : string {
			// TODO: Implement render() method.
		}

	}


	class TestView implements ViewInterface {

		public $context = [];

		public $view;

		public function getContext( $key = null, $default = null ) {


		}

		public function with( $key, $value = null ) {

			if ( is_array( $key ) ) {
				$this->context = array_merge( $this->context, $key );
			} else {
				$this->context[ $key ] = $value;
			}

			return $this;

		}

		public function toResponse() :ResponseInterface{
			// TODO: Implement toResponse() method.
		}

		public function toString() : string {
			// TODO: Implement toString() method.
		}

	}