<?php


	namespace Tests\unit\Exceptions;


	use Exception;
	use PHPUnit\Framework\TestCase;
	use Tests\stubs\TestResponseService;
	use Tests\TestRequest;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Factories\ExceptionHandlerFactory;

	class DebugErrorHandlerTest extends TestCase {


		/** @test */
		public function exceptions_are_rendered_with_whoops () {


			$handler = $this->newErrorHandler();

			$exception = new Exception('Whoops Exception');

			ob_start();
			$handler->transformToResponse( $this->createRequest(), $exception );
			$output = ob_get_clean();


			$this->assertStringContainsString('Whoops Exception', $output);


		}

		/** @test */
		public function debug_data_is_provided_in_the_json_response_for_ajax_request () {

			$handler = $this->newErrorHandler(TRUE);

			$exception = new Exception('Whoops Exception');

			ob_start();
			$handler->transformToResponse( $this->createRequest(), $exception );
			$output = ob_get_clean();

			$output = json_decode( $output, true )['error'];
			$this->assertArrayHasKey( 'type', $output );
			$this->assertArrayHasKey( 'message', $output );
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
