<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Handlers;

	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\Controllers\Web\WebController;
	use WPEmerge\Handlers\ClosureAction;
	use WPEmerge\Handlers\ControllerAction;
	use WPEmerge\Factories\HandlerFactory;
	use PHPUnit\Framework\TestCase;

	class HandlerFactoryTest extends TestCase {

		/**
		 * @var HandlerFactory
		 */
		private $factory;


		protected function setUp() : void {

			parent::setUp();

			$this->factory = new HandlerFactory(TEST_CONFIG['controllers'], new BaseContainerAdapter());

		}

		/** @test */
		public function a_passed_closure_always_results_in_a_closure_handler () {

			$foo = function () {

				return 'foo';

			};

			$handler = $this->factory->createUsing($foo);

			$this->assertInstanceOf(ClosureAction::class, $handler);

			$this->assertSame($foo, $handler->raw() );

		}

		/** @test */
		public function a_fully_qualified_namespaced_class_callable_results_in_a_controller () {

			$controller = WebController::class . '@handle';

			$handler = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ControllerAction::class, $handler);

			$this->assertEquals([WebController::class, 'handle'], $handler->raw() );

		}

		/** @test */
		public function an_array_callable_results_in_a_controller() {

			$controller = [ WebController::class , 'handle'];

			$handler = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ControllerAction::class, $handler);

			$this->assertEquals([WebController::class, 'handle'], $handler->raw() );

		}

		/** @test */
		public function non_existing_controller_classes_raise_an_exception() {

			$this->expectExceptionMessage("[InvalidController, 'handle'] is not a valid callable.");

			$controller ='InvalidController@handle';

			$this->factory->createUsing($controller);


		}

		/** @test */
		public function non_callable_methods_on_a_controller_raise_an_exception () {

			$this->expectExceptionMessage("[" . WebController::class . ", 'invalidMethod'] is not a valid callable.");

			$controller = [ WebController::class , 'invalidMethod'];

			$this->factory->createUsing($controller);


		}

		/** @test */
		public function passing_an_array_with_the_method_prefixed_with_an_at_sign_also_works () {

			$controller = [ WebController::class , '@handle'];

			$handler = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ControllerAction::class, $handler);
			$this->assertEquals([WebController::class, 'handle'], $handler->raw() );

		}

		/** @test */
		public function web_controllers_can_be_resolved_without_the_full_namespace () {

			$controller = [ 'WebController' , 'handle'];
			$handler = $this->factory->createUsing($controller);
			$this->assertInstanceOf(ControllerAction::class, $handler);
			$this->assertEquals([WebController::class, 'handle'], $handler->raw() );

			$controller ='WebController@handle';
			$handler = $this->factory->createUsing($controller);
			$this->assertInstanceOf(ControllerAction::class, $handler);
			$this->assertEquals([WebController::class, 'handle'], $handler->raw() );




		}

		/** @test */
		public function admin_controllers_can_be_resolved_without_the_full_namespace () {


			$controller = [ 'AdminController' , 'handle'];

			$result = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ControllerAction::class, $result);

		}

		/** @test */
		public function ajax_controllers_can_be_resolved_without_the_full_namespace() {

			$controller = [ 'AjaxController' , 'handle'];

			$result = $this->factory->createUsing($controller);

			$this->assertInstanceOf(ControllerAction::class, $result);

		}


	}

