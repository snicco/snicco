<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Exceptions;

	use Exception;
	use Tests\traits\AssertsResponse;
    use Tests\UnitTest;
	use Tests\stubs\TestException;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ResponseFactory;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\ExceptionHandling\ProductionErrorHandler;
    use WPEmerge\Factories\ErrorHandlerFactory;
    use WPEmerge\Http\Request;
    use WPEmerge\Http\Response;

    class ProductionErrorHandlerTest extends UnitTest {

		use AssertsResponse;

        protected function beforeTestRun()
        {

            ApplicationEvent::make($this->container = $this->createContainer());
            ApplicationEvent::fake();
            $this->container->instance(ProductionErrorHandler::class, ProductionErrorHandler::class);
            $this->container->instance(ResponseFactory::class, $this->createResponseFactory());

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);

        }



		/** @test */
		public function inside_the_routing_flow_the_exceptions_get_transformed_into_response_objects() {


			$handler = $this->newErrorHandler();


			$response = $handler->transformToResponse( new TestException('Sensitive Info') );

			$this->assertInstanceOf(Response::class, $response);
            $this->assertOutput('Internal Server Error', $response);
            $this->assertStatusCode(500 , $response);
			ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);


		}

		/** @test */
		public function outside_the_routing_flow_exceptions_will_lead_to_script_termination () {

			$handler = $this->newErrorHandler();

			ob_start();
			$handler->handleException( new TestException('Sensitive Info') );
			$output = ob_get_clean();

			$this->assertStringContainsString('Internal Server Error', $output);

			ApplicationEvent::assertDispatched(UnrecoverableExceptionHandled::class);

		}

		/** @test */
		public function for_ajax_requests_the_content_type_is_set_correctly () {

			$handler = $this->newErrorHandler(true);

			$response = $handler->transformToResponse( new TestException('Sensitive Info') );

			$this->assertInstanceOf(Response::class, $response);
			$this->assertStatusCode(500, $response);
			$this->assertContentType('application/json', $response);

			$this->assertSame('Internal Server Error', json_decode( $response->getBody()->__toString() ) );

			ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

		}

		/** @test */
		public function an_unspecified_exception_gets_converted_into_a_500_internal_server_error () {

			$handler = $this->newErrorHandler();

			$response = $handler->transformToResponse( new TestException('Sensitive Info') );

			$this->assertInstanceOf(Response::class, $response);
			$this->assertStatusCode(500, $response);
			$this->assertContentType('text/html', $response);
			$this->assertOutput('Internal Server Error', $response);


            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

		}

		/** @test */
		public function an_exception_can_have_custom_rendering_logic () {

			$handler = $this->newErrorHandler();

			$response = $handler->transformToResponse( new RenderableException() );

			$this->assertInstanceOf(Response::class, $response);
			$this->assertStatusCode(500, $response);
			$this->assertContentType('text/html', $response);
			$this->assertOutput('Foo', $response);

            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

		}

		/** @test */
		public function renderable_exceptions_MUST_return_a_response_object () {

            $handler = $this->newErrorHandler();

            $response = $handler->transformToResponse( new WrongReturnTypeException() );

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(500, $response);
            $this->assertContentType('text/html', $response);

            // We rethrow the exception.
            $this->assertOutput('Internal Server Error', $response);

            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

		}

		/** @test */
		public function renderable_exceptions_receive_the_current_request_and_a_response_factory_instance () {

		    $this->container->instance(
		        Request::class,
                TestRequest::from('GET', 'foo')->withAttribute('foo', 'bar')
            );

			$handler = $this->newErrorHandler();

			$response = $handler->transformToResponse( new ExceptionWithDependencyInjection() );

			$this->assertInstanceOf(Response::class, $response);
			$this->assertStatusCode(403, $response);
			$this->assertContentType('text/html', $response);
			$this->assertOutput('bar', $response);

            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

		}

		/** @test */
		public function a_custom_error_handler_can_replace_the_default_response_object () {

           $this->container->instance(ProductionErrorHandler::class, CustomErrorHandler::class);

            $handler = $this->newErrorHandler();

            $response = $handler->transformToResponse( new Exception('Sensitive Data nobody should read') );

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStatusCode(500, $response);
            $this->assertContentType('text/html', $response);
            $this->assertOutput('Custom Error Message', $response);

            ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

		}



		private function newErrorHandler (bool $is_ajax = false ) : ProductionErrorHandler {

			return ErrorHandlerFactory::make($this->container, false, $is_ajax);

		}


	}

	class RenderableException extends Exception {

		public function render (ResponseFactory $factory) {

		    return $factory->html('Foo')->withStatus(500);

		}

	}

	class WrongReturnTypeException extends Exception  {

        public function render () {

             return 'foo';

        }

    }

	class ExceptionWithDependencyInjection extends Exception {

		public function render( Request $request, ResponseFactory $response_factory) {

            return $response_factory
                ->html($request->getAttribute('foo'))
                ->withStatus(403);

		}

	}

	class CustomErrorHandler extends ProductionErrorHandler {


	    protected function defaultResponse() : Response
        {
            return $this->response->html('Custom Error Message')->withStatus(500);
        }


    }

