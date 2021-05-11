<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Support;

	use Illuminate\Config\Repository;

	class VariableBag extends Repository {

		/** @var string */
		private $prefix = 'globals';

		public function setPrefix(string $prefix) : VariableBag {

			$this->prefix = $prefix;

			return $this;

		}

		public function getPrefix() : string {

			return $this->prefix;

		}

		public function add(array $globals) {

			$this->set($globals);

		}


	}