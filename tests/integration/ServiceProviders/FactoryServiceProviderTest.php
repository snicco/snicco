<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

    use Tests\stubs\TestApp;
    use Tests\TestCase;
    use WPMvc\Factories\ConditionFactory;
	use WPMvc\Factories\RouteActionFactory;
	use WPMvc\Factories\ViewComposerFactory;
	use WPMvc\Routing\ControllerAction;
	use WPMvc\View\ViewComposer;

	class FactoryServiceProviderTest extends TestCase {

		/** @test */
		public function the_factory_service_provider_is_set_up_correctly() {

			$this->assertInstanceOf( RouteActionFactory::class, TestApp::resolve( RouteActionFactory::class ) );
			$this->assertInstanceOf( ViewComposerFactory::class, TestApp::resolve( ViewComposerFactory::class ) );
			$this->assertInstanceOf( ConditionFactory::class, TestApp::resolve( ConditionFactory::class ) );

		}

		/** @test */
		public function the_controller_namespace_can_be_configured_correctly() {

			/** @var RouteActionFactory $factory */
			$factory = TestApp::resolve( RouteActionFactory::class );

			$this->assertInstanceOf( ControllerAction::class, $factory->createUsing( 'AdminController@handle' ) );
			$this->assertInstanceOf( ControllerAction::class, $factory->createUsing( 'WebController@handle' ) );
			$this->assertInstanceOf( ControllerAction::class, $factory->createUsing( 'AjaxController@handle' ) );


		}

		/** @test */
		public function the_view_composer_namespace_can_be_configured_correctly() {


			/** @var ViewComposerFactory $factory */
			$factory = TestApp::resolve( ViewComposerFactory::class );

			$this->assertInstanceOf( ViewComposer::class, $factory->createUsing( 'FooComposer@compose' ) );

		}



	}