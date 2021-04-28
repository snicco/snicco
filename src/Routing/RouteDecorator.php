<?php


	namespace WPEmerge\Routing;

	use BadMethodCallException;
	use WPEmerge\Routing\Conditions\UrlCondition;

	class RouteDecorator {


		/** @var \WPEmerge\Routing\Router */
		private $router;

		public const allowed_attributes = [
			'middleware',
			'name',
			'namespace',
			'prefix',
			'where',
			'methods',
		];

		private const pass_back_to_router = [
			'get',
			'post',
			'put',
			'patch',
			'delete',
			'options',
			'any',
		];

		private $decorated_attributes = [];

		/**
		 * @var \WPEmerge\Routing\Route|null
		 */
		private $route;


		public function __construct( Router $router, Route $route = null ) {

			$this->router = $router;
			$this->route  = $route;

		}

		public function decorate( $attribute, $value ) : RouteDecorator {


			$this->decorated_attributes[ $attribute ] = $value;

			return $this;

		}

		public function __call( $method, $parameters ) {

			// $args = is_array($parameters[0]) ? $parameters[0] : $parameters;


			if ( in_array( $method, self::pass_back_to_router ) ) {

				return $this->registerRoute( $method, ...$parameters );

			}

			if ( in_array( $method, self::allowed_attributes ) ) {

				return $this->decorate( $method, $parameters[0] );

			}

			throw new BadMethodCallException( sprintf(
				'Method %s::%s does not exist.', static::class, $method
			) );

		}

		private function registerRoute( $method, $url, $action = null ) : Route {

			$route = $this->router->{$method}( $url, $action );

			array_walk( $this->decorated_attributes, function ( $value, $method ) use ( $route ) {

				$route->{$method}( $value );

			} );


			return $route;

		}


	}