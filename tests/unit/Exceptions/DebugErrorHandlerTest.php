<?php


	declare( strict_types = 1 );

	namespace Tests\unit\Exceptions;


	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\AssertsResponse;
	use Tests\stubs\TestException;
	use Tests\TestRequest;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\Exceptions\DebugErrorHandler;
	use WPEmerge\Factories\ErrorHandlerFactory;

	class DebugErrorHandlerTest extends TestCase {

		use AssertsResponse;

		protected function setUp() : void {

			parent::setUp();
			ApplicationEvent::make();
			ApplicationEvent::fake();

		}

		protected function tearDown() : void {

			parent::tearDown();

			ApplicationEvent::setInstance(null );

		}

		/** @test */
		public function exceptions_are_rendered_with_whoops () {


			$handler = $this->newErrorHandler();

			$exception = new TestException('Whoops Exception');

			ob_start();
			$handler->transformToResponse( $exception );
			$output = ob_get_clean();

			$this->assertStringContainsString('Whoops Exception', $output);

			ApplicationEvent::assertDispatchedTimes(UnrecoverableExceptionHandled::class, 1);

		}




		/** @test */
		public function debug_data_is_provided_in_the_json_response_for_ajax_request () {

			$handler = $this->newErrorHandler(TRUE);


			$exception = new TestException('Whoops Ajax Exception');

			ob_start();
			$handler->transformToResponse(  $exception );
			$response = ob_get_clean();

			$output = json_decode( $response, true )['error'];
			$this->assertSame( 'Tests\stubs\TestException', $output['type'] );
			$this->assertSame( 'Whoops Ajax Exception', $output['message'] );
			$this->assertArrayHasKey( 'code', $output );
			$this->assertArrayHasKey( 'trace', $output );
			$this->assertArrayHasKey( 'file', $output );
			$this->assertArrayHasKey( 'line', $output );
			$this->assertArrayHasKey( 'trace', $output );

			ApplicationEvent::assertDispatchedTimes(UnrecoverableExceptionHandled::class, 1);


		}


		private function newErrorHandler (bool $is_ajax = false ) : DebugErrorHandler {

			$request = TestRequest::from('GET', 'foo');
			$request->overrideGlobals();

			return ErrorHandlerFactory::make(
				new BaseContainerAdapter(),
				true,
				$is_ajax
			);

		}



	}
