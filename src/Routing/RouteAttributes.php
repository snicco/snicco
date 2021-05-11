<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\SetsRouteAttributes;
	use WPEmerge\Support\Arr;

	class RouteAttributes implements SetsRouteAttributes {


		/**
		 * @var \WPEmerge\Routing\Route
		 */
		private $route;

		public function __construct(Route $route ) {

			$this->route = $route;
		}

		public function populateInitial( array $attributes ) {

			if ( $methods = Arr::get($attributes, 'methods') ) {

				$this->methods( $methods );

			}

			if ( $middleware = Arr::get($attributes, 'middleware') ) {

				$this->middleware( $middleware );

			}

			if ( $namespace = Arr::get($attributes, 'namespace') ) {

				$this->namespace( $namespace );

			}

			if ( $name = Arr::get($attributes, 'name')) {

				$this->name( $name );

			}

			if ( $conditions = Arr::get($attributes, 'where') ) {

				foreach ( $conditions->all() as $condition ) {

					$this->where( $condition );

				}

			}

		}

		public function mergeGroup (RouteGroup $group ) {

			if ( $methods = $group->methods() ) {

				$this->methods($methods);

			}

			if ( $middleware = $group->middleware() ) {

				$this->middleware($middleware);

			}

			if ( $namespace = $group->namespace() ) {

				$this->namespace($namespace);

			}

			if ( $name = $group->name() ) {

				$this->name($name);

			}

			if ( $conditions = $group->conditions() ) {

				foreach ( $conditions->all() as $condition ) {

					$this->where( $condition );

				}

			}

		}

		public function middleware( $middleware ) {

			$this->route->middleware($middleware);

		}

		public function name( string $name ) {

			$this->route->name($name);

		}

		public function namespace( string $namespace ) {

			$this->route->namespace($namespace);

		}

		public function methods( $methods ) {

			$this->route->methods($methods);

		}

		public function where() {

			$args = func_get_args();

			$args = Arr::flattenOnePreserveKeys($args);

			$this->route->where(...$args);

		}

		public function defaults( array $defaults ) {

			$this->route->defaults($defaults);

		}

	}