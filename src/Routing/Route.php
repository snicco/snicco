<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteCondition;
	use WPEmerge\Contracts\SetsRouteAttributes;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Routing\RouteSignatureParameters;
	use WPEmerge\Support\Url;
	use WPEmerge\Support\UrlParser;
	use WPEmerge\Support\Arr;
	use WPEmerge\Support\Str;
	use WPEmerge\Traits\SetRouteAttributes;

	class Route implements RouteCondition, SetsRouteAttributes {

		use SetRouteAttributes;

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

		/** @var \WPEmerge\Contracts\RouteAction */
		private $compiled_action;

		/** @var \WPEmerge\Routing\ConditionBlueprint[] */
		private $conditions;

		/**
		 * @var array
		 */
		private $middleware;

		/** @var string */
		private $namespace;

		/** @var string */
		private $name;

		/**
		 * @var ConditionInterface[]
		 */
		private $compiled_conditions = [];

		private $regex;

		/** @var array */
		private $defaults;

		public function __construct( array $methods, string $url, $action, array $attributes = [] ) {

			$this->methods    = $methods;
			$this->url        = $this->replaceOptional( Url::normalizePath( $url ) );
			$this->action     = $action;
			$this->namespace  = $attributes['namespace'] ?? null;
			$this->middleware = $attributes['middleware'] ?? null;

		}

		public function compile() : CompiledRoute {

			return new CompiledRoute( [
				'action'     => $this->action,
				'middleware' => $this->middleware ?? [],
				'conditions' => $this->conditions ?? [],
				'namespace'  => $this->namespace ?? '',
				'defaults'  => $this->defaults ?? [],
			] );

		}

		public function and( ...$regex ) : Route {

			$regex_array = $this->normalizeRegex( Arr::flattenOnePreserveKeys( $regex ) );

			$this->url = $this->parseUrlWithRegex( $regex_array );

			$this->regex = $regex_array;

			return $this;

		}

		public function andAlpha() : Route {

			return $this->addRegexToSegment(func_get_args(), '[a-zA-Z]+');

		}

		public function andNumber() : Route {

			return $this->addRegexToSegment(func_get_args(), '[0-9]+');

		}

		public function andAlphaNumerical() : Route {

			return $this->addRegexToSegment(func_get_args(), '[a-zA-Z0-9]+');

		}

		public function andEither (string $segment, array $pool) : Route {

			return $this->addRegexToSegment($segment, implode('|', $pool) );

		}

		public function getMethods() : array {

			return $this->methods;

		}

		public function getRegexConstraints() {

			return $this->regex;

		}

		public function getName() : ?string {

			return $this->name;

		}

		public function getConditions() : ?array {

			return $this->conditions;

		}

		public function getUrl() : string {

			return $this->url;

		}

		public function getCompiledConditions() : array {

			return $this->compiled_conditions;
		}

		public function compileConditions( ConditionFactory $condition_factory ) : Route {

			$this->compiled_conditions = $condition_factory->compileConditions( $this );

			return $this;

		}

		private function replaceOptional( string $url_pattern ) : string {


			$optionals = UrlParser::replaceOptionalMatch( $url_pattern );

			foreach ( $optionals as $optional ) {

				$optional = preg_quote( $optional, '/' );

				$pattern = sprintf( "#(%s)#", $optional );

				$url_pattern = preg_replace_callback( $pattern, function ( $match ) {

					$cleaned_match = Str::between( $match[0], '{', '?' );

					return sprintf( "[/{%s}]", $cleaned_match );

				}, $url_pattern, 1 );

			}

			while ( $this->hasMultipleOptionalSegments( rtrim( $url_pattern, '/' ) ) ) {

				$this->combineOptionalSegments( $url_pattern );

			}

			return rtrim( $url_pattern, '/' );

		}

		private function hasMultipleOptionalSegments( string $url_pattern ) : bool {

			$count = preg_match_all( '/(?<=\[).*?(?=])/', $url_pattern, $matches );

			return $count > 1;

		}

		private function combineOptionalSegments( string &$url_pattern ) {

			preg_match( '/(\[(.*?)])/', $url_pattern, $matches );

			$first = $matches[0];

			$before = Str::before( $url_pattern, $first );
			$after  = Str::afterLast( $url_pattern, $first );

			$url_pattern = $before . rtrim( $first, ']' ) . rtrim( $after, '/' ) . ']';

		}

		private function normalizeRegex( $regex ) : array {

			if ( is_int( Arr::firstEl( array_keys( $regex ) ) ) ) {

				return Arr::combineFirstTwo( $regex );

			}

			return $regex;

		}

		private function parseUrlWithRegex( array $regex ) : string {

			$segments = UrlParser::segments( $this->url );

			$segments = array_filter( $segments, function ( $segment ) use ( $regex ) {

				return isset( $regex[ $segment ] );

			} );

			$url = $this->url;

			foreach ( $segments as $segment ) {

				$pattern = sprintf( "/(%s(?=\\}))/", preg_quote( $segment, '/' ) );;

				$url = preg_replace_callback( $pattern, function ( $match ) use ( $regex ) {

					return $match[0] . ':' . $regex[ $match[0] ];

				}, $url, 1 );

			}

			return rtrim( $url, '/' );

		}

		private function addRegexToSegment( $segments, string $pattern ) : Route {

			collect( $segments )
				->flatten()
				->each( function ( $segment ) use ( $pattern ) {

					$this->and( $segment, $pattern );

				});

			return $this;

		}

	}