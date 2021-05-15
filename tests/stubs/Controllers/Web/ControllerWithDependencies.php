<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\Bar;
	use Tests\stubs\Foo;
	use Tests\stubs\TestRequest;
    use WPEmerge\Http\Request;
    use WPEmerge\Http\Response;

	class ControllerWithDependencies {

		/**
		 * @var Foo
		 */
		private $foo;

		public function __construct( Foo $foo ) {

			$this->foo = $foo;

		}

		public function handle( Request $request )  {

		    return $this->foo->foo . '_controller';


		}

		public function withMethodDependency( Request $request, Bar $bar )  {

			return $this->foo->foo . $bar->bar . '_controller';


		}



	}