<?php


	declare( strict_types = 1 );


	namespace Snicco\Contracts;

    use Snicco\Routing\Route;
    use Snicco\Routing\RoutingResult;

    interface RouteMatcher {

		public function add( Route $route, array $methods );

		public function find(string $method, string $path) : RoutingResult;

		public function isCached() :bool;

	}