<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Traits;

	use Closure;
    use WPEmerge\Routing\ConditionBlueprint;
    use WPEmerge\Routing\Conditions\TrailingSlashCondition;
    use WPEmerge\Routing\Route;
	use WPEmerge\Support\Arr;

	trait SetRouteAttributes {

        /**
         * @var array
         */
        private $methods;

        /**
         * @var string
         */
        private $url;

        /** @var string|Closure|array */
        private $action;

        /** @var ConditionBlueprint[] */
        private $conditions = [];

        /**
         * @var array
         */
        private $middleware;

        /** @var string */
        private $namespace;

        /** @var string */
        private $name;

        /** @var array */
        private $regex = [];

        /** @var array */
        private $defaults = [];

        /**
         * @var Closure|null
         */
        private $wp_query_filter = null;

        /** @var array */
        private $segment_names = [];

        /**
         * @var array
         */
        private $segments = [];

        /**
         * @var bool
         */
        private $trailing_slash = false;

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

        public function wpquery(Closure $callback) : Route
        {

            $this->wp_query_filter = $callback;

            /** @var Route $this */
            return $this;

        }

        /**
         *
         *
         *
         * FLUENT INTERFACE FOR BUILDING REGEX
         *
         *
         *
         */

        public function and(...$regex) : Route
        {

            $regex_array = $this->normalizeRegex($regex);


            /** @todo This needs to added instead of replaced regex */
            $this->regex[] = $regex_array;

            /** @var Route $this */
            return $this;

        }

        public function andAlpha() : Route
        {

            return $this->addRegexToSegment(func_get_args(), '[a-zA-Z]+');

        }

        public function andNumber() : Route
        {

            return $this->addRegexToSegment(func_get_args(), '[0-9]+');

        }

        public function andAlphaNumerical() : Route
        {
            return $this->addRegexToSegment(func_get_args(), '[a-zA-Z0-9]+');

        }

        public function andEither(string $segment, array $pool) : Route
        {

            return $this->addRegexToSegment($segment, implode('|', $pool));

        }

        public function andOnlyTrailing() : Route
        {

            $this->where(TrailingSlashCondition::class);

            $this->trailing_slash = true;

            /** @var Route $this */
            return $this;

        }

        private function normalizeRegex($regex) : array
        {

            $regex = Arr::flattenOnePreserveKeys($regex);

            if (is_int(Arr::firstEl(array_keys($regex)))) {

                return Arr::combineFirstTwo($regex);

            }

            return $regex;

        }

        private function addRegexToSegment($segments, string $pattern) : Route
        {

            collect($segments)
                ->flatten()
                ->each(function ($segment) use ($pattern) {

                    $this->and($segment, $pattern);

                });

            /** @var Route $this */
            return $this;

        }

    }