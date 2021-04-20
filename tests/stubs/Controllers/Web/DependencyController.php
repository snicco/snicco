<?php


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\Bar;
	use Tests\stubs\Foo;
	use Tests\stubs\TestResponse;
	use WPEmerge\Requests\Request;

	class DependencyController {

		/**
		 * @var \Foo
		 */
		private $foo;

		public function __construct( Foo $foo ) {

			$this->foo = $foo;

		}

		public function handle( Request $request ) {

			$request->body = $this->foo->foo . '_web_controller';

			return new TestResponse($request);

		}

		public function withMethodDependency( Request $request, Bar $bar ) {

			$request->body = $this->foo->foo . $bar->bar;

			return new TestResponse($request);

		}



	}