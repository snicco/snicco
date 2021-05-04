<?php


	namespace Tests\stubs;

	class Bar {

		public $bar = 'bar';

		public function __toString() {

			return $this->bar;

		}

	}