<?php


	namespace WPEmerge\Traits;

	use WPEmerge\Routing\Route;
	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Routing\RouteBlueprint;
	use WPEmerge\Routing\RouteDecorator;

	trait HoldsRouteBlueprint {



		// public function where( $condition ) : RouteBlueprint {
		//
		// 	if ( ! $condition instanceof ConditionInterface ) {
		// 		$condition = func_get_args();
		// 	}
		//
		// 	$condition = $this->router->mergeConditionAttribute(
		// 		$this->getAttribute( 'condition', null ),
		// 		$condition
		// 	);
		//
		// 	return $this->attribute( 'condition', $condition );
		//
		// }


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

		public function get( string $url = null, $action = null  ) : RouteDecorator {

			return $this->addRoute(['GET', 'HEAD'], $url, $action);

		}

		public function post( string $url = null , $action = null  ) : RouteDecorator {

			return $this->addRoute(['POST'], $url, $action);

		}

		public function put( string $url = null, $action = null ) : RouteDecorator {

			return $this->addRoute(['PUT'], $url, $action);

		}

		public function patch( string $url = null, $action = null) : RouteDecorator {

			return $this->addRoute(['PATCH'], $url, $action);

		}

		public function delete( string $url = null, $action = null ) : RouteDecorator {

			return $this->addRoute(['DELETE'], $url, $action);

		}

		public function options(string $url = null , $action = null ) : RouteDecorator {

			return $this->addRoute(['OPTIONS'], $url, $action);

		}

		public function any(string $url = null, $action = null ) : RouteDecorator {

			return $this->addRoute(
				[ 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS' ],
				$url,
				$action
			);
		}

		public function match( array $verbs, $url, $action = null ) : RouteDecorator {

			return $this->addRoute(array_map('strtoupper',$verbs), $url, $action);

		}

		public function __call( $method, $parameters ) {

			if ( ! in_array($method, RouteDecorator::allowed_attributes ) ) {

				throw new \BadMethodCallException(
					'Method: ' . $method . 'does not exists on '. get_class($this)
				);

			}

			return ( ( new RouteDecorator($this) )->decorate($method, $parameters[0]) );

		}

	}