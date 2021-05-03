<?php


	namespace Tests\stubs\Middleware;

	use Closure;
	use Tests\stubs\Foo;
	use WPEmerge\Requests\Request;

	class FooooooMiddleware {

		/**
		 * @var \Tests\stubs\Foo
		 */
		private $foo_dependency;

		public function __construct( Foo $foo_dependency) {

			$GLOBALS['route_middleware_resolved'] = true;

			$this->foo_dependency = $foo_dependency;

		}

		public function handle ( Request $request, Closure $next, string $bar = '' ) {

			$request->body = 'foo' . $bar . ':' . $this->foo_dependency->foo;

			return $next($request);


		}

	}


