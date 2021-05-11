<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Support\Arr;

	class TestView implements ViewInterface {

		private $context = [];

		private $name;

		public function context( string $key = null, $default = null ) {

			if ( $key === null ) {
				return $this->context;
			}

			return Arr::get( $this->context, $key, $default );

		}

		public function with( $key, $value = null ) {

			if ( is_array( $key ) ) {
				$this->context = array_merge( $this->context(), $key );
			} else {
				$this->context[ $key ] = $value;
			}

		}

		public function toResponse() : ResponseInterface {


		}

		public function getName() {

			return $this->name;

		}

		public function setName( $name ) {

			$this->name = $name;

		}

		public function toString() : string {

		}

	}