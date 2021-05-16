<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
	use WPEmerge\Factories\ConditionFactory;
	use WPEmerge\Factories\HandlerFactory;
	use WPEmerge\Factories\ViewComposerFactory;
	use WPEmerge\Handlers\ControllerAction;
	use WPEmerge\ViewComposers\ViewComposer;

	class FactoryServiceProviderTest extends IntegrationTest {




		/** @test */
		public function the_factory_service_provider_is_set_up_correctly() {

		    $this->newTestApp();

			$this->assertInstanceOf( HandlerFactory::class, TestApp::resolve( HandlerFactory::class ) );
			$this->assertInstanceOf( ViewComposerFactory::class, TestApp::resolve( ViewComposerFactory::class ) );
			$this->assertInstanceOf( ConditionFactory::class, TestApp::resolve( ConditionFactory::class ) );


		}

		/** @test */
		public function the_controller_namespace_can_be_configured_correctly() {

            $this->newTestApp([
                'controllers' => [
                    'web' => 'Tests\stubs\Controllers\Web',
                    'admin' => 'Tests\stubs\Controllers\Admin',
                    'ajax' => 'Tests\stubs\Controllers\Ajax',
                ]
            ]);


			/** @var HandlerFactory $factory */
			$factory = TestApp::resolve( HandlerFactory::class );

			$this->assertInstanceOf( ControllerAction::class, $factory->createUsing( 'AdminController@handle' ) );
			$this->assertInstanceOf( ControllerAction::class, $factory->createUsing( 'WebController@handle' ) );
			$this->assertInstanceOf( ControllerAction::class, $factory->createUsing( 'AjaxController@handle' ) );


		}

		/** @test */
		public function the_view_composer_namespace_can_be_configured_correctly() {


            $this->newTestApp([
                'composers' => [
                    'Tests\stubs\ViewComposers',
                ]
            ]);

			/** @var ViewComposerFactory $factory */
			$factory = TestApp::resolve( ViewComposerFactory::class );

			$this->assertInstanceOf( ViewComposer::class, $factory->createUsing( 'FooComposer@compose' ) );

		}



	}