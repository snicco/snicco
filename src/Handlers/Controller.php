<?php


	namespace WPEmerge\Handlers;

	use Closure;
	use WPEmerge\Contracts\ResolveControllerMiddleware;
	use WPEmerge\Contracts\RouteAction;
	use WPEmerge\MiddlewareResolver;

	class Controller implements RouteAction, ResolveControllerMiddleware {


		/**
		 * @var array
		 */
		private $raw_callable;
		/**
		 * @var \Closure
		 */
		private $executable_callable;

		/**
		 * @var \WPEmerge\MiddlewareResolver
		 */
		private $middleware_resolver;

		/**
		 * Controller constructor.
		 *
		 * @param  array  $raw_callable
		 * @param  \Closure  $executable_callable
		 * @param  \WPEmerge\MiddlewareResolver  $resolver
		 */
		public function __construct(array $raw_callable, Closure $executable_callable, MiddlewareResolver $resolver ) {

			$this->raw_callable        = $raw_callable;
			$this->executable_callable = $executable_callable;
			$this->middleware_resolver = $resolver;

		}

		public function executeUsing(...$args) {

			$callable = $this->executable_callable;

			return $callable(...$args);

		}

		public function raw() {

			return $this->raw_callable;

		}


		public function resolveControllerMiddleware() : array {


			return $this->middleware_resolver->resolveFor($this->raw_callable);


		}

	}