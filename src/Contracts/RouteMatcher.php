<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


    use WPEmerge\Routing\CompiledRoute;

    interface RouteMatcher {

		// public function _add( $methods, string $uri, $handler );

		public function add( CompiledRoute $route, array $methods );

		public function find(string $method, string $path);

		public function isCached() :bool;


	}