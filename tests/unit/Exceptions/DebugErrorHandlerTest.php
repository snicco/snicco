<?php


	namespace Tests\unit\Exceptions;


	use Codeception\TestCase\WPTestCase;
	use Exception;
	use Tests\stubs\TestResponseService;
	use Tests\TestRequest;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Factories\ExceptionHandlerFactory;

	class DebugErrorHandlerTest extends WPTestCase {


		/** @test */
		public function exceptions_are_rendered_with_whoops () {


			$handler = $this->newErrorHandler();

			$exception = new Exception('Whoops Exception');

			$response = $handler->transformToResponse( $this->createRequest(), $exception );

			$this->assertStringContainsString('Whoops Exception', $response->getBody()->read(999));
			$this->assertSame(500, $response->getStatusCode());
			$this->assertContains('text/html', $response->getHeader('Content-Type'));


		}

		/** @test */
		public function debug_data_is_provided_in_the_json_response_for_ajax_request () {

			$handler = $this->newErrorHandler(TRUE);

			$exception = new Exception('Whoops Ajax Exception');

			$response = $handler->transformToResponse( $this->createRequest(), $exception );


			$output = json_decode( $response->getBody(), true )['error'];
			$this->assertSame( 'Exception', $output['type'] );
			$this->assertSame( 'Whoops Ajax Exception', $output['message'] );
			$this->assertArrayHasKey( 'code', $output );
			$this->assertArrayHasKey( 'trace', $output );
			$this->assertArrayHasKey( 'file', $output );
			$this->assertArrayHasKey( 'line', $output );

		}


		private function newErrorHandler (bool $is_ajax = false ) : ErrorHandlerInterface {

			return (( new ExceptionHandlerFactory(true, $is_ajax) ))->create(new TestResponseService());

		}

		private function createRequest() : TestRequest {

			return TestRequest::from('GET', 'foo');


		}

	}
