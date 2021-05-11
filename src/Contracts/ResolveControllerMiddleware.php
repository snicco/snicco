<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	interface ResolveControllerMiddleware {

		public function resolveControllerMiddleware() :array;

	}