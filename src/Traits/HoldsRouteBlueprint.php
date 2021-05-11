<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Traits;

	use WPEmerge\Routing\Route;
	use WPEmerge\Routing\RouteDecorator;
	use WPEmerge\Support\Arr;

	trait HoldsRouteBlueprint {


		public function get( string $url = '*', $action = null  ) : Route {

			return $this->addRoute(['GET', 'HEAD'], $url, $action);

		}

		public function post( string $url = '*' , $action = null  ) : Route {

			return $this->addRoute(['POST'], $url, $action);

		}

		public function put( string $url = '*', $action = null ) : Route {

			return $this->addRoute(['PUT'], $url, $action);

		}

		public function patch( string $url = '*', $action = null) : Route {

			return $this->addRoute(['PATCH'], $url, $action);

		}

		public function delete( string $url = '*', $action = null ) : Route {

			return $this->addRoute(['DELETE'], $url, $action);

		}

		public function options(string $url = '*' , $action = null ) : Route {

			return $this->addRoute(['OPTIONS'], $url, $action);

		}

		public function any(string $url = '*', $action = null ) : Route {

			return $this->addRoute(
				[ 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS' ],
				$url,
				$action
			);
		}

		public function match( $verbs, $url, $action = null ) : Route {

			$verbs = Arr::wrap($verbs);

			return $this->addRoute(array_map('strtoupper',$verbs), $url, $action);

		}


	}