<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	class RouteResult {


		/** @var Route */
		private $route;

		/** @var array */
		private $payload;


		public function __construct( ?Route $route, array $payload = [] ) {

			$this->route   = $route;
			$this->payload = $payload;

		}

		public function route() : ?Route {

			return $this->route;
		}


		public function capturedUrlSegmentValues() : array {

			return $this->payload;
		}


	}