<?php


	namespace WPEmerge\Routing;

	use WPEmerge\Helpers\Url;
	use WPEmerge\Support\Arr;

	class RouteGroup {


		private $namespace;

		private $url_prefix;

		private $name;

		private $middleware;

		/** @var ConditionBucket */
		private $conditions;

		private $methods;

		public function __construct( array $attributes = [] ) {

			$this->namespace  = $attributes['namespace'] ?? '';
			$this->url_prefix = $attributes['prefix'] ?? '';
			$this->name       = $attributes['name'] ?? '';
			$this->middleware = $attributes['middleware'] ?? [];
			$this->conditions = $attributes['where'] ?? ConditionBucket::createEmpty();
			$this->methods    = Arr::wrap($attributes['methods'] ?? []);
		}

		public function mergeWith( RouteGroup $old_group ) : RouteGroup {

			$this->methods = $this->mergeMethods($old_group->methods);

			$this->middleware = $this->mergeMiddleware($old_group->middleware);

			$this->name = $this->mergeName($old_group->name);

			$this->url_prefix = $this->mergePrefix($old_group->url_prefix);

			$this->conditions = $this->mergeConditions($old_group->conditions);

			return $this;


		}

		public function mergeIntoRoute( Route $route ) {

			if ( $this->methods ) {

				$route->methods( $this->methods );

			}

			if ( $this->middleware ) {

				$route->middleware( $this->middleware );

			}

			if ( $this->namespace ) {

				$route->namespace( $this->namespace );

			}

			if ( $this->name ) {

				$route->name( $this->name );

			}

			if ( $this->conditions ) {

				foreach ( $this->conditions->all() as $condition ) {

					$route->where( $condition );

				}

			}

		}

		public function prefix() {

			return $this->url_prefix;

		}

		private function mergeMiddleware(array $old_middleware) : array {

			return array_merge($old_middleware, $this->middleware);

		}

		private function mergeMethods( array $old_methods ) : array {

			return array_merge($old_methods, $this->methods);

		}

		private function mergeName( string $old  ) : string {

			// Remove leading and trailing dots.
			$new = preg_replace( '/^\.+|\.+$/', '', $this->name );
			$old = preg_replace( '/^\.+|\.+$/', '', $old );

			return trim($old . '.' . $new, '.');

		}

		private function mergePrefix( string $old_group_prefix ) : string {

			return Url::combinePath( $old_group_prefix, $this->url_prefix );

		}

		private function mergeConditions( ConditionBucket $old_conditions ) : ConditionBucket {

			return $this->conditions->combine($old_conditions);

		}

	}