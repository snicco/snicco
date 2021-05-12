<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Exceptions;

	use Exception;
	use Psr\Log\LoggerInterface;
	use Psr\Log\LogLevel;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\AssertsResponse;
	use Tests\stubs\Foo;
	use Tests\stubs\TestException;
	use Tests\stubs\TestLogger;
	use Tests\TestCase;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\ResponseInterface;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\Exceptions\ProductionErrorHandler;
	use WPEmerge\Facade\WP;
	use WPEmerge\Factories\ErrorHandlerFactory;
	use WPEmerge\Http\Response;

	class ProductionErrorHandlerTest extends TestCase {

		use AssertsResponse;

		/**
		 * @var \SniccoAdapter\BaseContainerAdapter
		 */
		private $container;

		protected function afterSetUp () {

			ApplicationEvent::make();
			ApplicationEvent::fake();

			$this->container = new BaseContainerAdapter();
			$this->container->instance(RequestInterface::class, $this->createRequest());
			$this->container->instance(ProductionErrorHandler::class, ProductionErrorHandler::class);
			$GLOBALS['test']['log'] = [];

			WP::shouldReceive('userId')->andReturn(10)->byDefault();

		}

		/** @test */
		public function inside_the_routing_flow_the_exceptions_get_transformed_into_response_objects() {


			$handler = $this->newErrorHandler();
			$handler->transformToResponse(new TestException('Sensitive Info'), $this->createRequest() );

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
		public function for_ajax_request_the_content_type_is_set_correctly () {

			$handler = $this->newErrorHandler(true);


			$response = $handler->transformToResponse( new TestException('Sensitive Info') );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertStatusCode(500, $response);
			$this->assertContentType('application/json', $response);
			$this->assertOutput('Internal Server Error', $response );

			ApplicationEvent::assertNotDispatched(UnrecoverableExceptionHandled::class);

		}

		/** @test */
		public function an_unspecified_exception_gets_converted_into_a_500_internal_server_error () {

			$handler = $this->newErrorHandler();

			$response = $handler->transformToResponse( new TestException('Sensitive Info') );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertStatusCode(500, $response);
			$this->assertContentType('text/html', $response);
			$this->assertOutput('Internal Server Error', $response);


		}

		/** @test */
		public function an_exception_can_have_custom_rendering_logic () {

			$handler = $this->newErrorHandler();

			$response = $handler->transformToResponse( new RenderableException(), $this->createRequest() );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertStatusCode(500, $response);
			$this->assertContentType('text/html', $response);
			$this->assertOutput('Foo', $response);

		}

		/** @test */
		public function renderable_exceptions_receive_the_current_request_and_a_response_factory_instance () {


			$handler = $this->newErrorHandler();

			$request = $this->createRequest();
			$request->attributes->set('message', 'foobar');

			$response = $handler->transformToResponse( new ExceptionWithDependencyInjection(), $request );

			$this->assertInstanceOf(ResponseInterface::class, $response);
			$this->assertStatusCode(500, $response);
			$this->assertContentType('text/html', $response);
			$this->assertOutput('foobar', $response);


		}

		/**
		 *
		 *
		 *
		 *
		 *
		 * LOGGING
		 *
		 *
		 *
		 *
		 *
		 *
		 */

		/** @test */
		public function exceptions_are_logged_with_the_default_logger_if_the_exception_doesnt_have_a_report_method() {

			$this->container->instance(LoggerInterface::class, $logger = new TestLogger());

			$handler = $this->newErrorHandler();

			$handler->transformToResponse(new Exception('Foobar'));

			$logger->assertHasLogLevelEntry(LogLevel::ERROR, 'Foobar');

		}

		/** @test */
		public function the_current_user_id_is_included_in_the_exception_context () {


			$this->container->instance(LoggerInterface::class, $logger = new TestLogger());

			$handler = $this->newErrorHandler();

			$handler->transformToResponse( $e = new Exception('Foobar'));

			$logger->assertHasLogEntry('Foobar', [ 'user_id' =>10, 'exception' => $e ] );

		}

		/** @test */
		public function the_user_id_is_not_included_if_there_is_none_logged_in () {

			WP::shouldReceive('userId')->andReturn(0);

			$this->container->instance(LoggerInterface::class, $logger = new TestLogger());

			$handler = $this->newErrorHandler();

			$handler->transformToResponse( $e = new Exception('Foobar'));

			$logger->assertHasLogEntry('Foobar', [ 'exception' => $e ] );

		}

		/** @test */
		public function exception_context_is_included_in_the_error_log_message_if_the_exception_has_a_context_method () {

			$this->container->instance(LoggerInterface::class, $logger = new TestLogger() );

			$handler = $this->newErrorHandler();



			$handler->transformToResponse( $e = new ContextException('TestMessage') );

			$logger->assertHasLogEntry('TestMessage', [ 'user_id' => 10, 'foo' => 'bar', 'exception' => $e ] );

		}

		/** @test */
		public function the_exception_object_is_included_in_the_log_context () {

			$this->container->instance(LoggerInterface::class, $logger = new TestLogger());

			$handler = $this->newErrorHandler();

			$handler->transformToResponse( $e = new Exception('Foobar') );

			$logger->assertHasLogEntry('Foobar', ['user_id' => 10, 'exception' => $e ]);

		}

		/** @test */
		public function exception_objects_can_have_custom_reporting_logic () {

			$this->assertEmpty($GLOBALS['test']['log']);

			$handler = $this->newErrorHandler();

			$handler->transformToResponse(new ReportableException('foobarlog'));

			$this->assertContains('foobarlog', $GLOBALS['test']['log']);

		}

		/** @test */
		public function exceptions_are_still_written_to_the_default_logger_after_custom_exceptions() {

			$this->container->instance(LoggerInterface::class, $logger = new TestLogger() );

			$handler = $this->newErrorHandler();

			$handler->transformToResponse( $e = new ReportableException('TestMessage') );

			$logger->assertHasLogEntry('TestMessage', [ 'user_id' => 10, 'exception' => $e ] );
			$this->assertContains('TestMessage', $GLOBALS['test']['log']);


		}

		/** @test */
		public function propagation_to_the_default_logger_can_be_stopped () {

			$this->container->instance(LoggerInterface::class, $logger = new TestLogger() );

			$handler = $this->newErrorHandler();

			$handler->transformToResponse(  new StopPropagationException('TestMessage') );

			$logger->assertHasNoLogEntries();
			$this->assertContains('TestMessage', $GLOBALS['test']['log']);

		}

		/** @test */
		public function logging_dependencies_are_resolved_from_the_container () {

			$this->assertEmpty($GLOBALS['test']['log']);

			$handler = $this->newErrorHandler();

			$handler->transformToResponse(new LogExceptionWithFooDependency('TestMessage'));

			$this->assertContains('TestMessage:foo', $GLOBALS['test']['log']);

		}

		/** @test */
		public function exceptions_can_be_ignored_for_reporting_from_a_child_class () {

			$this->container->instance(LoggerInterface::class, $logger = new TestLogger() );
			$this->container->instance(
				ProductionErrorHandler::class,
				CustomProductionErrorHandler::class
			);

			$this->assertEmpty($GLOBALS['test']['log']);

			$handler = $this->newErrorHandler();

			$handler->transformToResponse(new ReportableException('foo'));

			$logger->assertHasNoLogEntries();
			$this->assertEmpty($GLOBALS['test']['log']);


		}

		/** @test */
		public function the_global_context_can_be_overwritten_from_a_child_class () {

			$this->container->instance(LoggerInterface::class, $logger = new TestLogger());
			$this->container->instance(ProductionErrorHandler::class, CustomProductionErrorHandler::class);

			$handler = $this->newErrorHandler();

			$handler->transformToResponse( $e = new Exception('Foobar'));

			$logger->assertHasLogEntry('Foobar', ['foo' => 'bar', 'exception' => $e] );

		}


		private function newErrorHandler (bool $is_ajax = false ) : ProductionErrorHandler {

			return ErrorHandlerFactory::make($this->container, false, $is_ajax);

		}


	}

	class RenderableException extends Exception {

		public function render () {
			return new Response('Foo', 500);
		}

	}

	class ExceptionWithDependencyInjection  extends Exception {

		public function render( RequestInterface $request ) {

			return new Response($request->attribute('message', ''), 500);

		}

	}

	class ContextException extends Exception {

		public function context () : array {

			return ['foo' => 'bar'];

		}

	}

	class ReportableException extends Exception {

		public function report () {

			$GLOBALS['test']['log'][] = $this->getMessage();

		}

	}

	class StopPropagationException extends Exception {

		public function report () : bool {

			$GLOBALS['test']['log'][] = $this->getMessage();

			return false;

		}

	}

	class LogExceptionWithFooDependency extends Exception {

		public function report(Foo $foo) {

			$GLOBALS['test']['log'][] = $this->getMessage() . ':' . $foo->foo;

		}


	}

	class CustomProductionErrorHandler extends ProductionErrorHandler {

		protected $dont_report = [
			ReportableException::class,
		];

		protected function globalContext() : array {

			return ['foo'=>'bar'];

		}

	}