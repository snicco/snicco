<?php


	declare( strict_types = 1 );


	namespace WPMvc\Contracts;

	interface ResolveControllerMiddleware {

		public function resolveControllerMiddleware() :array;

	}