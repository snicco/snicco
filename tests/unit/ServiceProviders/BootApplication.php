<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use Mockery;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\TestApp;
	use WPEmerge\Application\Application;
	use WPEmerge\Facade\WordpressApi;
	use WpFacade\WpFacade;

	trait BootApplication {

		private function bootNewApplication( array $config = [] ) : Application {

			$this->reset();

			$app = TestApp::make( $c =  new BaseContainerAdapter() );

			$app->boot( $config );

			WpFacade::setFacadeContainer($c);

			return $app;

		}

		protected function tearDown() : void {

			parent::tearDown();

			TestApp::setApplication( null );

			WpFacade::clearResolvedInstances();

			Mockery::close();

		}

		private function reset() {

			TestApp::setApplication( null );

		}

		private function bootAndSimulateAjaxRequest() {

			$mock = Mockery::mock( WordpressApi::class );

			$mock->shouldReceive( 'isAdmin' )
			     ->andReturnFalse();
			$mock->shouldReceive( 'isAdminAjax' )
			     ->andReturnTrue();

			return $this->bootNewApplication( array_merge( $this->routeConfig(), [

				'testing' => [
					'enabled'  => true,
					'callable' => function () use ( $mock ) {

						$this->bootFacade();

						return $mock;

					},
				],

			] ) );

		}

		private function bootAndSimulateAdminRequest() {

			$mock = Mockery::mock( WordpressApi::class );

			$mock->shouldReceive( 'isAdmin' )
			     ->andReturnTrue();
			$mock->shouldReceive( 'isAdminAjax' )
			     ->andReturnFalse();

			return $this->bootNewApplication( array_merge( $this->routeConfig(), [

				'testing' => [
					'enabled'  => true,
					'callable' => function () use ( $mock ) {

						$this->bootFacade();

						return $mock;

					},
				],

			] ) );

		}


	}