<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Controllers\Web;

	use Tests\fixtures\TestDependencies\Bar;
	use Tests\fixtures\TestDependencies\Foo;
    use Snicco\Http\Psr7\Request;

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