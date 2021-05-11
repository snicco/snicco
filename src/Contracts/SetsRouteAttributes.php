<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	interface SetsRouteAttributes {

		public function middleware($middleware);

		public function name(string $name);

		public function namespace(string $namespace);

		public function methods($methods);

		public function where();

		public function defaults (array $defaults);

	}