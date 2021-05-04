<?php


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\Bar;
	use Tests\stubs\Foo;
	use Tests\stubs\TestResponse;
	use Tests\TestRequest;

	class ControllerWithDependencies {

		/**
		 * @var Foo
		 */
		private $foo;

		public function __construct( Foo $foo ) {

			$this->foo = $foo;

		}

		public function handle( TestRequest $request ) : TestResponse {

			$request->body = $this->foo->foo . '_controller';

			return new TestResponse($request);

		}

		public function withMethodDependency( TestRequest $request, Bar $bar ) : TestResponse {

			$request->body = $this->foo->foo . $bar->bar . '_controller';

			return new TestResponse($request);

		}



	}