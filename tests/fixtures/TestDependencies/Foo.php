<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\TestDependencies;

	class Foo {

		public $foo = 'foo';

		public function __toString() {

			return $this->foo;

		}

	}