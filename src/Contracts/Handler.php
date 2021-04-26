<?php


	namespace WPEmerge\Contracts;

	interface Handler {

		public function executeUsing(...$args);

	}