<?php


	namespace Tests\stubs\Middleware;

	use Closure;
	use WPEmerge\Requests\Request;

	class FooMiddleware {

		/**
		 * @var \Tests\stubs\Middleware\FooDependency
		 */
		private $foo_dependency;

		public function __construct(FooDependency $foo_dependency) {

			$this->foo_dependency = $foo_dependency;
		}

		public function handle ( Request $request, Closure $next, string $bar = '' ) {

			$request->body = 'foo' . $bar . ':' . $this->foo_dependency->dependency;

			return $next($request);


		}

	}


	class FooDependency {

		public $dependency = 'foo_dependency_';


	}