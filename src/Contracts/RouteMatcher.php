<?php


	declare( strict_types = 1 );


	namespace BetterWP\Contracts;


    use BetterWP\Routing\Route;
    use BetterWP\Routing\RoutingResult;

    interface RouteMatcher {


		public function add( Route $route, array $methods );

		public function find(string $method, string $path) : RoutingResult;

		public function isCached() :bool;


	}