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
		 * @var \WPEmerge\Routing\ConditionBucket
		 */
		private $conditions;


		public function __construct( Router $router ) {

			$this->router = $router;
			$this->conditions = new ConditionBucket();
			$this->decorated_attributes['where'] = $this->conditions;

		}

		public function decorate( $attribute, $value ) : RouteDecorator {


			if ($attribute === 'where') {

				$this->conditions->add($value);

				// $this->decorated_attributes[ $attribute ] = array_merge(
				//
				// 	$this->decorated_attributes['where'] ?? [] , [$value]
				//
				// );

				return $this;

			}

			$this->decorated_attributes[ $attribute ] = $value;

			return $this;

		}

		public function __call( $method, $parameters ) {

			// $args = is_array($parameters[0]) ? $parameters[0] : $parameters;


			if ( in_array( $method, self::pass_back_to_router ) ) {

				return $this->registerRoute( $method, ...$parameters );

			}

			if ( $method === 'where') {

				return $this->decorate(
					$method,
					is_array($parameters[0]) ? $parameters[0] : $parameters
				);

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