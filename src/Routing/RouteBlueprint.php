<?php


	namespace WPEmerge\Routing;

	use Closure;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\Helpers\UrlParser;
	use WPEmerge\Helpers\HasAttributes;
	use WPEmerge\Contracts\ConditionInterface;

	class RouteBlueprint {

		use HasAttributes;

		/** @var \WPEmerge\Routing\Router */
		private $router;

		/** @var ViewServiceInterface */
		protected $view_service;


		public function __construct( Router $router, ViewServiceInterface $view_service ) {

			$this->router       = $router;
			$this->view_service = $view_service;

		}

		/**
		 * Match requests using one of the specified methods.
		 *
		 * @param  string[]  $methods
		 *
		 */
		private function methods( array $methods ) :RouteBlueprint {

			$methods = $this->router->mergeMethodsAttribute(
				 $this->getAttribute( 'methods', [] ),
				 $methods
			);

			return $this->attribute( 'methods', $methods );
		}

		private function url( string $url_pattern, array $where = [] ) :RouteBlueprint {

			$url = UrlParser::normalize( $url_pattern );

			return $this->where( 'url', $url, $where );

		}

		/**
		 * @param  string|array|ConditionInterface|closure  $condition
		 *
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function where( $condition ) : RouteBlueprint {

			if ( ! $condition instanceof ConditionInterface ) {
				$condition = func_get_args();
			}

			$condition = $this->router->mergeConditionAttribute(
				$this->getAttribute( 'condition', null ),
				$condition
			);

			return $this->attribute( 'condition', $condition );

		}

		/**
		 * @param  string|string[]  $middleware
		 */
		public function middleware( $middleware ) :RouteBlueprint {

			$middleware = $this->router->mergeMiddlewareAttribute(
				(array) $this->getAttribute( 'middleware', [] ),
				(array) $middleware
			);

			return $this->attribute( 'middleware', $middleware );

		}

		public function namespace( string $namespace ) :RouteBlueprint {

			$namespace = $this->router->mergeNamespaceAttribute(
				$this->getAttribute( 'namespace', '' ),
				$namespace
			);

			return $this->attribute( 'namespace', $namespace );
		}

		public function name( string $name ) :RouteBlueprint {

			return $this->attribute( 'name', $name );
		}

		/**
		 *
		 * @param  Closure|string  $routes  Closure or path to file.
		 *
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function group( $routes ) :void {

			$this->router->group( $this->getAttributes(), $routes );
		}

		public function handle( $handler = null ) : void {


			$this->attribute( 'handler', $handler );

			$route = $this->router->route( $this->getAttributes() );

			$this->router->addRoute( $route );


		}

		public function view( string $url, string $view_name, array $context = [] ) {

			$this->url($url);
			$this->methods(['GET', 'HEAD']);
			$this->handle(function () use ($view_name, $context) {

				$view = $this->view_service->make($view_name);
				$view->with($context);

				return $view;

			});


		}

		public function get( string $url ) : RouteBlueprint {

			$this->url($url);

			return $this->methods( [ 'GET', 'HEAD' ] );
		}

		public function post(string $url ) : RouteBlueprint {

			$this->url($url);

			return $this->methods( [ 'POST' ] );
		}

		public function put(string $url) : RouteBlueprint {

			$this->url($url);

			return $this->methods( [ 'PUT' ] );
		}

		public function patch(string $url) : RouteBlueprint {

			$this->url($url);
			return $this->methods( [ 'PATCH' ] );
		}

		public function delete(string $url) : RouteBlueprint {

			$this->url($url);
			return $this->methods( [ 'DELETE' ] );
		}

		public function options(string $url ) : RouteBlueprint {

			$this->url($url);
			return $this->methods( [ 'OPTIONS' ] );
		}

		public function any(string $url) : RouteBlueprint {

			$this->url($url);
			return $this->methods( [ 'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS' ] );
		}

		public function match(array $verbs, $url ) : RouteBlueprint {

			$this->methods($verbs);
			return $this->url($url);

		}
	}
