<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\ViewComposers;

	use Snicco\Contracts\ViewInterface;
    use Tests\fixtures\TestDependencies\Bar;
    use Tests\fixtures\TestDependencies\Foo;

    class FooComposer {


		private Bar $bar;

		public function __construct( Bar $bar ) {

			$this->bar = $bar;
		}

		public function compose(ViewInterface $view, Foo $foo ) {

			$view->with(['foo' => $foo->foo . $this->bar->bar]);

		}

	}