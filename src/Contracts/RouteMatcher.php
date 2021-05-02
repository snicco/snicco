<?php


	namespace WPEmerge\Contracts;

	interface RouteMatcher {


		public function add( $methods, string $uri, $handler );

		public function findRoute(string $method, string $path);

		public function isCached() :bool;
	}