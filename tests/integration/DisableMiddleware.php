<?php


	namespace Tests\integration;

	use Tests\stubs\TestApp;
	use WPEmerge\Middleware\SubstituteBindings;
	use Mockery as m;

	trait DisableMiddleware {


		public function disableMiddleware() {

			TestApp::container()->instance('middleware.disable', true);

		}

	}