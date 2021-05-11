<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ViewComposers;

	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\Foo;
	use Tests\stubs\ViewComposers\FooComposer;
	use WPEmerge\Contracts\ViewComposer;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\View\PhpView;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\Factories\ViewComposerFactory;

	class ViewComposerFactoryTest extends TestCase {


		/**
		 * @var HandlerFactory
		 */
		private $factory;


		protected function setUp() : void {

			parent::setUp();

			$this->factory = new ViewComposerFactory(TEST_CONFIG['composers'], new BaseContainerAdapter());

		}

		/** @test */
		public function a_closure_can_be_a_view_composer () {

			$foo = function (ViewInterface $view, Foo $foo) {

				$view->with(['foo' => $foo->foo]);

			};

			$composer = $this->factory->createUsing($foo);

			$this->assertInstanceOf(ViewComposer::class, $composer);

			$composer->executeUsing($view = $this->newPhpView());

			$this->assertSame('foo', $view->getContext('foo') );


		}

		/** @test */
		public function a_fully_qualified_namespaced_class_can_be_a_composer () {

			$controller = FooComposer::class . '@compose';

			$composer = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ViewComposer::class, $composer);

			$composer->executeUsing($view = $this->newPhpView());

			$this->assertSame('foobar', $view->getContext('foo') );


		}

		/** @test */
		public function an_array_callable_can_be_a_composer() {

			$controller = [ FooComposer::class , 'compose'];

			$composer = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ViewComposer::class, $composer);


			$composer->executeUsing($view = $this->newPhpView());

			$this->assertEquals('foobar', $view->getContext('foo'));


		}

		/** @test */
		public function non_existing_composer_classes_raise_an_exception() {

			$this->expectExceptionMessage("[FooController, 'handle'] is not a valid callable.");

			$controller ='FooController@handle';

			$this->factory->createUsing($controller);


		}

		/** @test */
		public function non_callable_methods_on_a_composer_raise_an_exception () {

			$this->expectExceptionMessage("[" . FooComposer::class . ", 'invalidMethod'] is not a valid callable.");

			$controller = [ FooComposer::class , 'invalidMethod'];

			$this->factory->createUsing($controller);


		}

		/** @test */
		public function passing_an_array_with_the_method_prefixed_with_an_at_sign_also_works () {

			$controller = [ FooComposer::class , '@compose'];

			$composer = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ViewComposer::class, $composer);

			$composer->executeUsing($view = $this->newPhpView());

			$this->assertEquals('foobar', $view->getContext('foo'));


		}

		/** @test */
		public function composers_can_be_resolved_without_the_fqn () {


			$controller = [ 'FooComposer' , 'compose'];

			$composer = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ViewComposer::class, $composer);

			$composer->executeUsing($view = $this->newPhpView());

			$this->assertEquals('foobar', $view->getContext('foo'));


		}

		/** @test */
		public function if_no_method_is_specified_compose_is_assumed () {

			$controller = FooComposer::class;

			$composer = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ViewComposer::class, $composer);

			$composer->executeUsing($view = $this->newPhpView());

			$this->assertSame('foobar', $view->getContext('foo') );

		}


		private function newPhpView() : PhpView {

			$engine = \Mockery::mock(PhpViewEngine::class);

			return new PhpView($engine);

		}

	}
