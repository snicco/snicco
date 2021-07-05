<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\ViewComposers;

	use Tests\fixtures\TestDependencies\Bar;
	use Tests\fixtures\TestDependencies\Foo;
	use WPMvc\Contracts\ViewInterface;

	class FooComposer {


		/**
		 * @var Bar
		 */
		private $bar;

		public function __construct( Bar $bar ) {

			$this->bar = $bar;
		}

		public function compose(ViewInterface $view, Foo $foo ) {

			$view->with(['foo' => $foo->foo . $this->bar->bar]);

		}

	}