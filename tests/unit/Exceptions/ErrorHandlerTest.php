<?php


	namespace Tests\unit\Exceptions;

	use PHPUnit\Framework\TestCase;
	use Psr\Http\Message\ResponseInterface;
	use Tests\stubs\TestResponseService;
	use Tests\TestRequest;
	use Whoops\RunInterface;
	use WPEmerge\Exceptions\AuthorizationException;
	use WPEmerge\Exceptions\ErrorHandler;
	use WPEmerge\Exceptions\Exception;
	use WPEmerge\Exceptions\NotFoundException;

	class ErrorHandlerTest extends TestCase {


		/** @test */
		public function a_not_found_exception_gets_handled_correctly() {

			$handler = $this->newErrorHandler();

			$exception = new NotFoundException();

			$response = $handler->transformToResponse( $this->createRequest(), $exception );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertSame(404, $response->getStatusCode());

		}

		/** @test */
		public function exceptions_are_only_logged_in_production () {


			file_put_contents(TESTS_DIR . DS . 'test.log.php', '');

			$handler = $this->newErrorHandler();

			$exception = new Exception();

			$response = $handler->transformToResponse( $this->createRequest(), $exception );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertSame(500, $response->getStatusCode());

			$log = file_get_contents(TESTS_DIR . DS. 'test.log.php');
			unlink(TESTS_DIR . DS . 'test.log.php');
			$this->assertTrue( empty( $log ) );

		}

		/** @test */
		public function in_debug_mode_ajax_errors_are_returned_as_json () {


			$handler = $this->newErrorHandler(TRUE);

			$exception = new Exception();

			$ajax_request = $this->createRequest()->simulateAjax();

			$response = $handler->transformToResponse( $ajax_request, $exception );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertSame(500, $response->getStatusCode());

			$type = $response->getHeaderLine('Content-Type');

			$this->assertSame('application/json', $type);


		}

		/** @test */
		public function debug_data_is_provided_in_the_json_response_for_ajax_request () {

			$handler = $this->newErrorHandler(TRUE);

			$exception = new Exception();

			$ajax_request = $this->createRequest()->simulateAjax();

			$response = $handler->transformToResponse( $ajax_request, $exception );

			$body = $response->getBody();

			$response_text = '';

			while ( ! $body->eof() ) {

				$response_text .= $body->read( 4096 );

			}

			$response_json = json_decode( $response_text, true );
			$this->assertArrayHasKey( 'exception', $response_json );
			$this->assertArrayHasKey( 'message', $response_json );
			$this->assertArrayHasKey( 'trace', $response_json );
			$this->assertArrayHasKey( 'file', $response_json );
			$this->assertArrayHasKey( 'line', $response_json );

		}

		/** @test */
		public function exceptions_are_rethrown_in_debug_mode_if_not_ajax_and_not_whoops_enabled() {

			$this->expectException( Exception::class );
			$this->expectExceptionMessage( 'Rethrown exception' );

			$exception = new Exception( 'Rethrown exception' );

			$handler = $this->newErrorHandler(TRUE);

			$handler->transformToResponse($this->createRequest(), $exception);


		}

		/** @test */
		public function if_enabled_and_if_debug_exceptions_are_rendered_with_whoops () {

			$exception = new Exception( 'Whoops Exception' );

			$handler = $this->newErrorHandlerWithWhoops();

			$response = $handler->transformToResponse($this->createRequest(), $exception);

			$this->assertSame(
				'Whoops Exception',
				$response->getBody()->read( strlen( $exception->getMessage() ) )
			);


		}

		/** @test */
		public function authentication_request_get_redirected_with_a_403_status_code () {

			$handler = $this->newErrorHandler();

			$exception = new AuthorizationException();

			$request = $this->createRequest()->withAddedHeader('Referer', 'https://example.com' );

			$response = $handler->transformToResponse( $request, $exception );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertSame(302, $response->getStatusCode());
			$this->assertSame('https://example.com', $response->getHeaderLine('Location'));

		}


		private function newErrorHandler(bool $debug = false) :ErrorHandler {

			return new ErrorHandler(new TestResponseService(), null , $debug );


		}

		private function newErrorHandlerWithWhoops () :ErrorHandler {

			return new ErrorHandler(new TestResponseService(), new TestWhoops() , true );

		}

		private function createRequest() : TestRequest {

			return TestRequest::from('GET', 'foo');


		}

	}

	class TestWhoops implements RunInterface {


		public function pushHandler( $handler ) {
			// TODO: Implement pushHandler() method.
		}

		public function popHandler() {
			// TODO: Implement popHandler() method.
		}

		public function getHandlers() {
			// TODO: Implement getHandlers() method.
		}

		public function clearHandlers() {
			// TODO: Implement clearHandlers() method.
		}

		public function register() {
			// TODO: Implement register() method.
		}

		public function unregister() {
			// TODO: Implement unregister() method.
		}

		public function allowQuit( $exit = null ) {
			// TODO: Implement allowQuit() method.
		}

		public function silenceErrorsInPaths( $patterns, $levels = 10240 ) {
			// TODO: Implement silenceErrorsInPaths() method.
		}

		public function sendHttpCode( $code = null ) {
			// TODO: Implement sendHttpCode() method.
		}

		public function sendExitCode( $code = null ) {
			// TODO: Implement sendExitCode() method.
		}

		public function writeToOutput( $send = null ) {
			// TODO: Implement writeToOutput() method.
		}

		public function handleException( $exception ) {

			echo $exception->getMessage();

		}

		public function handleError( $level, $message, $file = null, $line = null ) {
			// TODO: Implement handleError() method.
		}

		public function handleShutdown() {
			// TODO: Implement handleShutdown() method.
		}

	}