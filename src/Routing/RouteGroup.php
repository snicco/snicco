<?php


	declare( strict_types = 1 );


	namespace BetterWP\Routing;

	use BetterWP\Support\Url;
	use BetterWP\Support\Arr;

	class RouteGroup {


		private $namespace;

		private $url_prefix;

		private $name;

		private $middleware;

		/** @var ConditionBucket */
		private $conditions;

		private $methods;

		public function __construct( array $attributes = [] ) {

			$this->namespace  = Arr::get( $attributes, 'namespace', '' );
			$this->url_prefix = Arr::get( $attributes, 'prefix', '' );
			$this->name       = Arr::get( $attributes, 'name', '' );
			$this->middleware = Arr::get( $attributes, 'middleware', [] );

			$this->conditions = Arr::get( $attributes, 'where', ConditionBucket::createEmpty() );

			$this->conditions = $this->conditions instanceof ConditionBucket
                ? $this->conditions
                : new ConditionBucket($this->conditions);

			$this->methods    = Arr::wrap( Arr::get( $attributes, 'methods', [] ) );
            $this->no_action  = Arr::get( $attributes, 'noAction', null );

		}

		public function mergeWith( RouteGroup $old_group ) : RouteGroup {

			$this->methods = $this->mergeMethods( $old_group->methods );

			$this->middleware = $this->mergeMiddleware( $old_group->middleware );

			$this->name = $this->mergeName( $old_group->name );

			$this->url_prefix = $this->mergePrefix( $old_group->url_prefix );

			$this->conditions = $this->mergeConditions( $old_group->conditions );

			$this->no_action = $this->mergeNoAction($old_group->no_action);

			return $this;


		}

		public function prefix () {

			return $this->url_prefix;

		}

		public function namespace() : string {

			return $this->namespace;
		}

		public function name() : string {

			return $this->name;
		}

		public function middleware() : array {

			return $this->middleware;
		}

		public function conditions() : ConditionBucket {

			return $this->conditions;

		}

		public function methods() : array {

			return $this->methods;

		}

		private function mergeMiddleware( array $old_middleware ) : array {

			return array_merge( $old_middleware, $this->middleware );

		}

		private function mergeMethods( array $old_methods ) : array {

			return array_merge( $old_methods, $this->methods );

		}

		private function mergeName( string $old ) : string {

			// Remove leading and trailing dots.
			$new = preg_replace( '/^\.+|\.+$/', '', $this->name );
			$old = preg_replace( '/^\.+|\.+$/', '', $old );

			return trim( $old . '.' . $new, '.' );

		}

		private function mergePrefix( string $old_group_prefix ) : string {

			return Url::combineRelativePath( $old_group_prefix, $this->url_prefix );

		}

		private function mergeConditions( ConditionBucket $old_conditions ) : ConditionBucket {

			return $this->conditions->combine( $old_conditions );

		}

        private function mergeNoAction( $old_group_no_action )
        {
            if ( isset($this->no_action) ) {

                return $this->no_action;

            }

            return $old_group_no_action;
        }


    }