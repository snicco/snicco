<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

    use Tests\AssertsResponse;
    use Tests\CreateResponseFactory;
    use Tests\stubs\TestResponseFactory;
    use Tests\TestCase;
    use WPEmerge\Contracts\ResponseFactoryInterface;
    use WPEmerge\Http\Response;

    class ViewRoutesTest extends TestCase {

		use SetUpRouter;
		use AssertsResponse;
        use CreateResponseFactory;

		/** @test */
		public function view_routes_work() {

			$this->createBindingsForViewController();

			$this->router->view('/foo', 'welcome.wordpress');

			$request = $this->request( 'GET', '/foo' );

			$response = $this->router->runRoute($request);

			$this->assertInstanceOf(Response::class, $response );
			$this->assertContentType('text/html', $response);
			$this->assertOutputContains('welcome.wordpress', $response);

		}

		/** @test */
		public function the_default_values_can_be_customized_for_view_routes() {

			$this->createBindingsForViewController();

			$this->router->view('/foo', 'welcome.wordpress', ['foo' => 'bar'], 201, ['Referer' => 'foobar']);

			$request = $this->request( 'GET', '/foo' );

			$response = $this->router->runRoute($request);

			$this->assertInstanceOf(Response::class, $response );

			$this->assertHeader('Referer', 'foobar', $response);
			$this->assertOutput('welcome.wordpress:foo=>bar', $response);
			$this->assertStatusCode(201, $response);

		}

		private function createBindingsForViewController () {

		    $this->container->instance(ResponseFactoryInterface::class, new TestResponseFactory());


		}

	}

