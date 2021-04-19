<?php


	namespace Tests\integration;

	use Tests\stubs\TestApp;
	use WPEmerge\Middleware\SubstituteModelBindings;
	use Mockery as m;

	trait DisableGlobalMiddleWare {


		public function disableGlobalMiddleware() {


			$container = TestApp::container();

			$mock_middleware = m::mock(SubstituteModelBindings::class);

			$mock_middleware->shouldReceive('handle')->andReturnUsing( function ($request, $next ) {

				return $next($request);

			});

			$container->swapInstance(SubstituteModelBindings::class, $mock_middleware );

		}

	}