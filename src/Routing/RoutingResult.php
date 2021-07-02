<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	class RoutingResult {

		/** @var Route|array|null */
		private $route;

		/** @var array */
		private $payload;

        /**
         *
         * @param Route|array|null $route
         * @param  array  $payload
         */
        public function __construct( $route, array $payload = [] ) {

			$this->route   = $route;
			$this->payload = $payload;

		}

		public function route(){

			return $this->route;
		}

		public function capturedUrlSegmentValues() : array {

			return array_map('rawurldecode',$this->payload);
		}


	}