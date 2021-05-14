<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use WPEmerge\Contracts\PhpViewInterface;
	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Support\Arr;

	class TestView implements PhpViewInterface {

		private $context = [];

		private $name;

		public function __construct(string $name) {

			$this->name = $name;
		}

		public function context( string $key = null, $default = null ) {

			if ( $key === null ) {
				return $this->context;
			}

			return Arr::get( $this->context, $key, $default );

		}

		public function with( $key, $value = null ) :ViewInterface {

			if ( is_array( $key ) ) {
				$this->context = array_merge( $this->context(), $key );
			} else {
				$this->context[ $key ] = $value;
			}

			return  $this;

		}

		public function toResponsable() : ResponseInterface {


		}

		public function toString() : string {

		}

		public function path() : string {

		}

		public function parent() : ?PhpViewInterface {

		}

		public function name() : string {

			return $this->name;

		}

	}