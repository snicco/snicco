<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	class Bar {

		public $bar = 'bar';

		public function __toString() {

			return $this->bar;

		}

	}