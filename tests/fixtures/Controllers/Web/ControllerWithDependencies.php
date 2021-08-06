<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Controllers\Web;

	use Snicco\Http\Psr7\Request;
    use Tests\fixtures\TestDependencies\Bar;
    use Tests\fixtures\TestDependencies\Foo;

    class ControllerWithDependencies {

		private Foo $foo;

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