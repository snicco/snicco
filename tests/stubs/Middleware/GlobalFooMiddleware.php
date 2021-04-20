<?php


	namespace Tests\stubs\Middleware;

	use Tests\stubs\Foo;
	use WPEmerge\Requests\Request;

	class GlobalFooMiddleware {



		public function __construct( Foo $foo_dependency) {

			$this->foo_dependency = $foo_dependency;

			$GLOBALS['global_middleware_resolved_from_container'] = true;

		}

		public function handle ( Request $request, \Closure $next, string $bar = '' ) {


			return $next($request);


		}

	}