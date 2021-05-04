<?php


	namespace Tests\stubs;

	class Baz {

		public $baz = 'baz';

		public function __toString() {

			return $this->baz;

		}

	}