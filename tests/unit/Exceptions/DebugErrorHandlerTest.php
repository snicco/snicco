<?php


	declare( strict_types = 1 );

	namespace Tests\unit\Exceptions;


	use Tests\helpers\AssertsResponse;
    use Tests\unit\UnitTest;
    use Tests\stubs\TestException;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\ExceptionHandling\DebugErrorHandler;
	use WPEmerge\Factories\ErrorHandlerFactory;

	class DebugErrorHandlerTest extends UnitTest {

		use AssertsResponse;

		protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();

        }

        protected function afterSetup() : void {

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


			return ErrorHandlerFactory::make(
				$this->createContainer(),
				true,
				$is_ajax
			);

		}



	}
