<?php


	namespace WPEmerge\Routing;

	use BadMethodCallException;
	use WPEmerge\Routing\Conditions\UrlCondition;

	class RouteDecorator {


		/** @var \WPEmerge\Routing\Router  */
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

		private $last_condition;

		public function __construct( Router $router ,Route $route = null ) {

			$this->router = $router;
			$this->route = $route;

		}

		public function decorate( $attribute, $value ) : RouteDecorator {

			if ( $attribute === 'where') {

				$this->where(...$value);

			}

			$this->decorated_attributes[ $attribute ] = $value;

			return $this;

		}

		public function __call( $method, $parameters ) {

			if ( $method === 'where') {

				return $this->decorate('where', $parameters);

			}

			if ( $this->route  ) {

				$this->route->{$method}(...$parameters);

				return $this;

			}

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

		private function registerRoute( $method, $url, $action = null ) : RouteDecorator {

			$route = $this->router->{$method}( $url, $action );

			array_walk( $this->decorated_attributes, function ( $value, $method ) use ( $route ) {

				$route->{$method}( $value );

			} );

			$this->route = $route;

			return $this;

		}

		private function where () {

			if ( ! $this->route ) {

				throw new \Exception(
					'Use one of the HTTP verb methods before creating conditions'
				);

			}

			$params = func_get_args();

			if ( $this->last_condition instanceof UrlCondition )  {

				$condition = $this->router->mergeConditionAttribute($this->lastCondition, $params);

			}

			if ( $condition = $this->route->getConditions('url') ) {

				$segments = $this->route->requiredSegments();

				if ( count($params) === 2 && in_array($params[0], $segments)) {

					$condition->setUrlWhere([$params[0] => $params[1]]);

				}

				if ( is_array($params) && array_values($segments) === array_keys($params[0])) {

					$condition->setUrlWhere($params[0]);

				}

			}




		}

		public function lastCondition($condition) {

			$this->last_condition = $condition;

		}

	}