<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\Bar;
	use Tests\stubs\Foo;
	use Tests\TestRequest;
	use WPEmerge\Http\Response;

	class ControllerWithDependencies {

		/**
		 * @var Foo
		 */
		private $foo;

		public function __construct( Foo $foo ) {

			$this->foo = $foo;

		}

		public function handle( TestRequest $request ) : Response {

			$request->body = $this->foo->foo . '_controller';

			return new Response($request->body);

		}

		public function withMethodDependency( TestRequest $request, Bar $bar ) : Response {

			$request->body = $this->foo->foo . $bar->bar . '_controller';

			return new Response($request->body);

		}



	}