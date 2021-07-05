<?php


	declare( strict_types = 1 );


	namespace WPMvc\Contracts;


    use WPMvc\Routing\Route;
    use WPMvc\Routing\RoutingResult;

    interface RouteMatcher {


		public function add( Route $route, array $methods );

		public function find(string $method, string $path) : RoutingResult;

		public function isCached() :bool;


	}