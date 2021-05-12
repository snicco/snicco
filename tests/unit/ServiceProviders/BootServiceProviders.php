<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use SniccoAdapter\BaseContainerAdapter;
	use Tests\SetUpDefaultMocks;
	use Tests\stubs\TestApp;
	use WPEmerge\Application\ApplicationConfig;
	use WPEmerge\Application\ApplicationEvent;

	trait BootServiceProviders {

		use SetUpDefaultMocks;


		/** @var \WPEmerge\Application\Application */
		protected $app;

		/** @var ApplicationConfig */
		protected $config;


		protected function setUp() : void {

			parent::setUp();

			$this->setUpWithConfig([]);

		}

		protected function tearDown ()  : void {

			TestApp::setApplication(null);

			unset($this->app);
			unset($this->config);

			parent::tearDown();

		}

		protected function setUpWithConfig ( array $config = [] ) {

			$container = new BaseContainerAdapter();
			$config = new ApplicationConfig($config);

			$this->config = $config;

			/** @var \WPEmerge\Contracts\ServiceProvider[] $provider_classes */
			$provider_classes = [];

			$this->app = TestApp::make($container);

			ApplicationEvent::make($container);
			ApplicationEvent::fake();

			if ( isset($this->needed_providers) && is_array($this->needed_providers)) {

				foreach ( $this->needed_providers as $provider ) {

					$provider_classes[] = new $provider($container, $config);

				}

				foreach ( $provider_classes as $provider_class ) {

					$provider_class->register();

				}

				foreach ( $provider_classes as $provider_class ) {

					$provider_class->bootstrap();

				}

			}

		}


	}