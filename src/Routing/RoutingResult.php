<?php


	declare( strict_types = 1 );


	namespace Snicco\Routing;

	class RoutingResult {

		/** @var Route|array|null */
		private $route;

		private array $payload;

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

            $values =  collect($this->payload)->map(function($value) {

                $value = rawurldecode($value);

                if ( is_numeric($value) ) {
                    $value = intval($value);
                }
                return $value;

            });

            return $values->all();


		}


	}