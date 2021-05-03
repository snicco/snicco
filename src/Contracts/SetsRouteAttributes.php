<?php


	namespace WPEmerge\Contracts;

	interface SetsRouteAttributes {

		public function middleware($middleware);

		public function name(string $name);

		public function namespace(string $namespace);

		public function methods($methods);

		public function where();

	}