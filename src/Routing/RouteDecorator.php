<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use BadMethodCallException;
	use WPEmerge\Support\Arr;

	class RouteDecorator {


		/** @var \WPEmerge\Routing\Router */
		protected $router;

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
			'match'
		];

		private $attributes = [];

		/**
		 * @var \WPEmerge\Routing\ConditionBucket
		 */
		private $conditions;

		public function __construct( Router $router ) {

			$this->router              = $router;
			$this->conditions          = ConditionBucket::createEmpty();
			$this->attributes['where'] = $this->conditions;

		}

		public function decorate( $called_method, $arguments ) : RouteDecorator {


			if ( $called_method === 'where') {

				$this->conditions->add($arguments);

				return $this;

			}

			$this->attributes[ $called_method ] = $arguments;

			return $this;

		}

		public function __call( $method, $parameters ) {


			if ( in_array( $method, self::pass_back_to_router ) ) {

				return $this->registerRoute( $method, ...$parameters );

			}

			if ( $method === 'where' || $method === 'middleware') {

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

			return $this->router->addRoute(
				[strtoupper($method)],
				$url,
				$action,
				$this->attributes
			);


		}

		public function match ($methods, $url, $action = null ) : Route {

			$methods = array_map('strtoupper', (Arr::wrap($methods)));

			return $this->router->addRoute(
				$methods,
				$url,
				$action,
				$this->attributes
			);

		}

		public function group ($callback) {

			$this->router->group($this->attributes , $callback );

		}

	}