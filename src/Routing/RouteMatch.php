<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	class RouteMatch {


		/** @var \WPEmerge\Routing\CompiledRoute */
		private $route;

		/** @var array */
		private $payload;


		public function __construct( ?CompiledRoute $route, array $payload ) {

			$this->route   = $route;
			$this->payload = $payload;

		}

		public function route() : ?CompiledRoute {

			return $this->route;
		}


		public function payload() : array {

			return $this->payload;
		}


	}