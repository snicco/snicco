<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Codeception\TestCase\WPTestCase;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Factories\ViewComposerFactory;
	use WPEmerge\Handlers\ControllerAction;
	use WPEmerge\ViewComposers\ViewComposer;

	class FactoryServiceProviderTest extends WPTestCase {

		use BootApplication;

		/** @test */
		public function the_factory_service_provider_is_set_up_correctly() {

			$app = $this->bootNewApplication();

			$this->assertInstanceOf( HandlerFactory::class, $app->resolve( HandlerFactory::class ) );
			$this->assertInstanceOf( ViewComposerFactory::class, $app->resolve( ViewComposerFactory::class ) );


		}

		/** @test */
		public function the_controller_namespace_can_be_configured_correctly() {

			$app = $this->bootNewApplication( [
				'controllers' => [

					'web' => 'Tests\stubs\Controllers\Web',
					'admin' => 'Tests\stubs\Controllers\Admin',
					'ajax' => 'Tests\stubs\Controllers\Ajax',

				],
			] );

			/** @var HandlerFactory $factory */
			$factory = $app->resolve( HandlerFactory::class );

			$this->assertInstanceOf( ControllerAction::class, $factory->createUsing( 'AdminController@handle' ) );
			$this->assertInstanceOf( ControllerAction::class, $factory->createUsing( 'WebController@handle' ) );
			$this->assertInstanceOf( ControllerAction::class, $factory->createUsing( 'AjaxController@handle' ) );


		}

		/** @test */
		public function the_view_composer_namespace_can_be_configured_correctly() {

			$app = $this->bootNewApplication( [
				'composers' => [

					'Tests\stubs\ViewComposers',

				],
			] );

			/** @var HandlerFactory $factory */
			$factory = $app->resolve( ViewComposerFactory::class );

			$this->assertInstanceOf( ViewComposer::class, $factory->createUsing( 'FooComposer@compose' ) );

		}

	}