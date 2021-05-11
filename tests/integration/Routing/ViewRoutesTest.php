<?php


	declare( strict_types = 1 );


	namespace Tests\integration\Routing;

	use PHPUnit\Framework\TestCase;
	use Tests\AssertsResponse;
	use Tests\stubs\TestResponseFactory;
	use WPEmerge\Contracts\ResponseFactoryInterface;
	use WPEmerge\Contracts\ResponseInterface;

	class ViewRoutesTest extends TestCase {

		use SetUpRouter;
		use AssertsResponse;

		/** @test */
		public function view_routes_work() {

			$this->bindResponseFactory();

			$this->router->view('/foo', 'welcome.wordpress');

			$request = $this->request( 'GET', '/foo' );

			$response = $this->router->runRoute($request);

			$this->assertInstanceOf(ResponseInterface::class, $response );

			$this->assertContentType('text/html', $response);
			$this->assertOutput('welcome.wordpress', $response);

		}

		/** @test */
		public function the_default_values_can_be_customized_for_view_routes() {

			$this->bindResponseFactory();

			$this->router->view('/foo', 'welcome.wordpress', ['foo' => 'bar'], 201, ['Referer' => 'foobar']);

			$request = $this->request( 'GET', '/foo' );

			$response = $this->router->runRoute($request);

			$this->assertInstanceOf(ResponseInterface::class, $response );

			$this->assertHeader('Referer', 'foobar', $response);
			$this->assertOutput('welcome.wordpress:foo=>bar', $response);
			$this->assertStatusCode(201, $response);

		}

		private function bindResponseFactory ( ) {

			$this->container->singleton(ResponseFactoryInterface::class, function () {

				return new TestResponseFactory();

			});

		}

	}

