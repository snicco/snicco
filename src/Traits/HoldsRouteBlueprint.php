<?php


	namespace WPEmerge\Traits;

	use WPEmerge\Routing\Route;
	use WPEmerge\Routing\RouteDecorator;
	use WPEmerge\Support\Arr;

	trait HoldsRouteBlueprint {

		public function group( $routes ) :void {

			$this->router->group( $this->getAttributes(), $routes );

		}

		public function view( string $url, string $view_name, array $context = [] ) {

			$this->url($url);
			$this->methods(['GET', 'HEAD']);
			$this->handle(function () use ($view_name, $context) {

				$view = $this->view_service->make($view_name);
				$view->with($context);

				return $view;

			});


		}

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

		public function __call( $method, $parameters ) {

			if ( ! in_array($method, RouteDecorator::allowed_attributes ) ) {

				throw new \BadMethodCallException(
					'Method: ' . $method . 'does not exists on '. get_class($this)
				);

			}

			if ( $method === 'where') {

				return ( ( new RouteDecorator($this) )->decorate(
					$method,
					is_array($parameters[0]) ? $parameters[0] : $parameters )
				);

			}


			return ( ( new RouteDecorator($this) )->decorate($method, $parameters[0]) );

		}

	}