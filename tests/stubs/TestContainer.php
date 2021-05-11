<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use Contracts\ContainerAdapter;

	class TestContainer implements ContainerAdapter {

		public function make( $abstract, array $parameters = [] ) {
			//
		}

		public function swapInstance( $abstract, $concrete ) {
			//
		}

		public function instance( $abstract, $instance ) {
			//
		}

		public function call( $callable, array $parameters = [] ) {
			//
		}

		public function bind( $abstract, $concrete ) {
			//
		}

		public function singleton( $abstract, $concrete ) {
			//
		}

		public function offsetExists( $offset ) {
			//
		}

		public function offsetGet( $offset ) {
			//
		}

		public function offsetSet( $offset, $value ) {
			//
		}

		public function offsetUnset( $offset ) {
			//
		}

	}