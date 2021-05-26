<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\TestDependencies;

	class Bar {

		public $bar = 'bar';

		public function __toString() {

			return $this->bar;

		}

	}