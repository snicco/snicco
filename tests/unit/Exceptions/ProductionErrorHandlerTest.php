<?php


	namespace Tests\unit\Exceptions;

	use PHPUnit\Framework\TestCase;
	use Tests\AssertsResponse;
	use WPEmerge\Contracts\ResponseInterface;
	use Tests\stubs\TestResponseService;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Exceptions\Exception;
	use WPEmerge\Factories\ExceptionHandlerFactory;

	class ProductionErrorHandlerTest extends TestCase {

		use AssertsResponse;

		/** @test */
		public function an_unspecified_exception_gets_converted_into_a_500_internal_server_error () {

			$handler = $this->newErrorHandler();

			$response = $handler->transformToResponse($this->createRequest(), new Exception('Sensitive Info') );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertStatusCode(500, $response);
			$this->assertContentType('text/html', $response);
			$this->assertOutput('Internal Server Error', $response);

		}

		/** @test */
		public function for_ajax_request_the_content_type_is_set_correctly () {

			$handler = $this->newErrorHandler();

			$ajax = $this->createRequest()->simulateAjax();

			$response = $handler->transformToResponse($ajax, new Exception('Sensitive Info') );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertStatusCode(500, $response);
			$this->assertContentType('application/json', $response);
			$this->assertOutput('Internal Server Error', $response );


		}



		private function newErrorHandler (bool $is_ajax = false ) : ErrorHandlerInterface {

			return (( new ExceptionHandlerFactory(false, $is_ajax) ))->create(new TestResponseService());

		}


	}