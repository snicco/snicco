<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use SniccoAdapter\BaseContainerAdapter;
	use Tests\stubs\TestApp;
	use WPEmerge\Application\Application;

	trait BootApplication {

		private function bootNewApplication( array $config = [] ) : Application {

			$app = TestApp::make(new BaseContainerAdapter());

			$app->boot( $config );

			return $app;

		}

		private function reset() {

			TestApp::setApplication(null );

		}


	}