<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\TestDependencies;

	class Foo {

		public string $foo = 'foo';

		public function __toString() {

			return $this->foo;

		}

	}