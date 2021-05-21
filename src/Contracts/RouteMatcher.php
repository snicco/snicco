<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RoutingResult;

    interface RouteMatcher {


		public function add( Route $route, array $methods );

		public function find(string $method, string $path) : RoutingResult;

		public function isCached() :bool;


	}