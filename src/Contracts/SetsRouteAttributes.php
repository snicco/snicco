<?php


	namespace WPEmerge\Contracts;

	interface SetsRouteAttributes {

		/**
		 * @param string|array $middleware
		 *
		 * @return mixed
		 */
		public function middleware($middleware);

		public function name(string $name);

		public function namespace(string $namespace);

		/**
		 * @param string|array $methods
		 *
		 * @return mixed
		 */
		public function methods($methods);

		public function where();

	}