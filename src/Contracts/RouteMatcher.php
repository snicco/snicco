<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	interface RouteMatcher {


		public function add( $methods, string $uri, $handler );

		public function find(string $method, string $path);

		public function isCached() :bool;

		public function canBeCached();
	}