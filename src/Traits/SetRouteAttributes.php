<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Traits;

	use WPEmerge\Routing\ConditionBlueprint;
	use WPEmerge\Routing\Route;
	use WPEmerge\Support\Arr;

	trait SetRouteAttributes {

		public function handle( $action ) : Route {

			$this->action = $action;

			/** @var Route $this */
			return $this;

		}

		public function namespace( string $namespace ) : Route {

			$this->namespace = $namespace;

			/** @var Route $this */
			return $this;

		}

		public function middleware( $middleware ) : Route {

			$middleware = Arr::wrap( $middleware );

			$this->middleware = array_merge( $this->middleware ?? [], $middleware );

			/** @var Route $this */
			return $this;

		}

		public function name( string $name ) : Route {

			// Remove leading and trailing dots.
			$name = preg_replace( '/^\.+|\.+$/', '', $name );

			$this->name = isset( $this->name ) ? $this->name . '.' . $name : $name;

			/** @var Route $this */
			return $this;


		}

		public function methods( $methods ) :Route {

			$this->methods = array_merge(
				$this->methods ?? [],
				array_map( 'strtoupper', Arr::wrap( $methods ) )
			);

			/** @var Route $this */
			return $this;

		}

		public function where() : Route {

			$args = func_get_args();

			$this->conditions[] = new ConditionBlueprint( $args );

			/** @var Route $this */
			return $this;

		}

		public function defaults ( array $defaults ) :Route {

			$this->defaults = $defaults;

			/** @var Route $this */
			return $this;

		}

	}