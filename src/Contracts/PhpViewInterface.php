<?php


	declare( strict_types = 1 );


	namespace WPMvc\Contracts;


	interface PhpViewInterface extends ViewInterface {

		public function path() : string;

		public function parent() : ?PhpViewInterface;

		public function name() : string;

	}