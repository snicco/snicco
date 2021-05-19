<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


    use WPEmerge\Routing\Route;

    interface RouteMatcher {


		public function add( Route $route, array $methods );

		public function find(string $method, string $path);

		public function isCached() :bool;


	}