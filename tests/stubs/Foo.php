<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	class Foo {

		public $foo = 'foo';

		public function __toString() {

			return $this->foo;

		}

	}