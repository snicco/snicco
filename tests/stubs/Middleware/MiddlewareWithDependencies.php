<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Middleware;

	use Closure;
	use Tests\stubs\Bar;
	use Tests\stubs\Foo;
	use Tests\TestRequest;

	class MiddlewareWithDependencies {

		/** @var \Tests\stubs\Foo */
		private $foo;
		/** @var \Tests\stubs\Bar */
		private $bar;


		public function __construct( Foo $foo, Bar $bar ) {

			$this->foo = $foo;
			$this->bar = $bar;

		}

		public function handle(TestRequest $request, Closure $next ) {

			$request->body = $this->foo . $this->bar;

			return $next($request);


		}


	}