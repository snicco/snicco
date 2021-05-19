<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\UnitTest;
    use Tests\traits\SetUpRouter;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Response;

    class ViewRoutesTest extends UnitTest {

		use SetUpRouter;

		/** @var ContainerAdapter */
        private $container;

        protected function beforeTestRun()
        {
            $this->newRouter( $this->container = $this->createContainer() );
            WP::setFacadeContainer($this->container);
        }

        protected function beforeTearDown()
        {

            Mockery::close();
            WP::clearResolvedInstances();
            WP::setFacadeContainer(null);

        }

		/** @test */
		public function view_routes_work() {

			$this->createBindingsForViewController();

			$this->router->view('/foo', 'welcome.wordpress');

			$this->router->loadRoutes();

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

            $this->router->loadRoutes();


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

