<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	interface Handler {

		public function executeUsing(...$args);

	}