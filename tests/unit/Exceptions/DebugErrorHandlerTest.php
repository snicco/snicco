<?php


	declare( strict_types = 1 );

	namespace Tests\unit\Exceptions;


	use SniccoAdapter\BaseContainerAdapter;
	use Tests\AssertsResponse;
    use Tests\CreateContainer;
    use Tests\stubs\TestException;
    use Tests\TestCase;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\Exceptions\DebugErrorHandler;
	use WPEmerge\Factories\ErrorHandlerFactory;

	class DebugErrorHandlerTest extends TestCase {

		use AssertsResponse;
        use CreateContainer;

		protected function afterSetup() : void {

		    ApplicationEvent::make($this->createContainer());
			ApplicationEvent::fake();

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


			return ErrorHandlerFactory::make(
				new BaseContainerAdapter(),
				true,
				$is_ajax
			);

		}



	}
