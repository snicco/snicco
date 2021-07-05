<?php


	declare( strict_types = 1 );


	namespace BetterWP\Contracts;

	interface ResolveControllerMiddleware {

		public function resolveControllerMiddleware() :array;

	}