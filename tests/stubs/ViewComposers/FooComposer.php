<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\ViewComposers;

	use Tests\stubs\Bar;
	use Tests\stubs\Foo;
	use WPEmerge\Contracts\ViewInterface;

	class FooComposer {


		/**
		 * @var \Tests\stubs\Bar
		 */
		private $bar;

		public function __construct( Bar $bar ) {

			$this->bar = $bar;
		}

		public function compose(ViewInterface $view, Foo $foo ) {

			$view->with(['foo' => $foo->foo . $this->bar->bar]);

		}

	}