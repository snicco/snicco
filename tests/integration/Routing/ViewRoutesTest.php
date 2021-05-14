<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

    use Tests\AssertsResponse;
    use Tests\CreatePsr17Factories;
    use Tests\TestCase;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Http\Response;

    class ViewRoutesTest extends TestCase {

		use SetUpRouter;
		use AssertsResponse;
        use CreatePsr17Factories;

		/** @test */
		public function view_routes_work() {

			$this->createBindingsForViewController();

			$this->router->view('/foo', 'welcome.wordpress');

			$request = $this->request( 'GET', '/foo' );

			$response = $this->router->runRoute($request);

			$this->assertInstanceOf(Response::class, $response );
			$this->assertContentType('text/html', $response);
			$this->assertStatusCode(200, $response);
			$this->assertOutputContains('VIEW:welcome.wordpress,CONTEXT:[]', $response);

		}

		/** @test */
		public function the_default_values_can_be_customized_for_view_routes() {

			$this->createBindingsForViewController();

			$this->router->view('/foo', 'welcome.wordpress', ['foo' => 'bar', 'bar' => 'baz'], 201, ['Referer' => 'foobar']);

			$request = $this->request( 'GET', '/foo' );

			$response = $this->router->runRoute($request);

			$this->assertInstanceOf(Response::class, $response );

			$this->assertHeader('Referer', 'foobar', $response);
			$this->assertOutput('VIEW:welcome.wordpress,CONTEXT:[foo=>bar,bar=>baz]', $response);
			$this->assertStatusCode(201, $response);

		}

		private function createBindingsForViewController () {

		    $this->container->instance(ResponseFactory::class, $this->responseFactory());


		}

	}

