<?php


    declare(strict_types = 1);


    namespace Tests\unit\Exceptions;

    use Exception;
    use Mockery;
    use Psr\Log\LoggerInterface;
    use Psr\Log\LogLevel;
    use SniccoAdapter\BaseContainerAdapter;
    use Tests\traits\AssertsResponse;
    use Tests\UnitTest;
    use Tests\stubs\Foo;
    use Tests\stubs\TestLogger;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\ExceptionHandling\ProductionErrorHandler;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ErrorHandlerFactory;
    use WPEmerge\Facade\WpFacade;

    class ProductionErrorHandlerLoggingTest extends UnitTest
    {

        use AssertsResponse;

        /**
         * @var BaseContainerAdapter
         */
        private $container;


        protected function beforeTestRun()
        {

            ApplicationEvent::make($this->container = $this->createContainer());
            ApplicationEvent::fake();
            $this->container->instance(ProductionErrorHandler::class, ProductionErrorHandler::class);
            $this->container->instance(ResponseFactory::class, $this->responseFactory());
            WpFacade::setFacadeContainer($this->container);
            WP::shouldReceive('userId')->andReturn(10)->byDefault();
            $GLOBALS['test']['log'] = [];

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            WP::setFacadeContainer(null);
            WP::clearResolvedInstances();
            Mockery::close();

        }

        /** @test */
        public function exceptions_are_logged_with_the_default_logger_if_the_exception_doesnt_have_a_report_method()
        {

            $this->container->instance(LoggerInterface::class, $logger = new TestLogger());

            $handler = $this->newErrorHandler();

            $handler->transformToResponse(new Exception('Foobar'));

            $logger->assertHasLogLevelEntry(LogLevel::ERROR, 'Foobar');

        }

        /** @test */
        public function the_current_user_id_is_included_in_the_exception_context()
        {


            $this->container->instance(LoggerInterface::class, $logger = new TestLogger());

            $handler = $this->newErrorHandler();

            $handler->transformToResponse($e = new Exception('Foobar'));

            $logger->assertHasLogEntry('Foobar', ['user_id' => 10, 'exception' => $e]);

        }

        /** @test */
        public function the_user_id_is_not_included_if_there_is_none_logged_in()
        {

            WP::shouldReceive('userId')->andReturn(0);

            $this->container->instance(LoggerInterface::class, $logger = new TestLogger());

            $handler = $this->newErrorHandler();

            $handler->transformToResponse($e = new Exception('Foobar'));

            $logger->assertHasLogEntry('Foobar', ['exception' => $e]);

        }

        /** @test */
        public function exception_context_is_included_in_the_error_log_message_if_the_exception_has_a_context_method()
        {

            $this->container->instance(LoggerInterface::class, $logger = new TestLogger());

            $handler = $this->newErrorHandler();

            $handler->transformToResponse($e = new ContextException('TestMessage'));

            $logger->assertHasLogEntry('TestMessage', [
                'user_id' => 10, 'foo' => 'bar', 'exception' => $e,
            ]);

        }

        /** @test */
        public function the_exception_object_is_included_in_the_log_context()
        {

            $this->container->instance(LoggerInterface::class, $logger = new TestLogger());

            $handler = $this->newErrorHandler();

            $handler->transformToResponse($e = new Exception('Foobar'));

            $logger->assertHasLogEntry('Foobar', ['user_id' => 10, 'exception' => $e]);

        }

        /** @test */
        public function exception_objects_can_have_custom_reporting_logic()
        {

            $this->assertEmpty($GLOBALS['test']['log']);

            $handler = $this->newErrorHandler();

            $handler->transformToResponse(new ReportableException('foobarlog'));

            $this->assertContains('foobarlog', $GLOBALS['test']['log']);

        }

        /** @test */
        public function exceptions_are_still_written_to_the_default_logger_after_custom_exceptions()
        {

            $this->container->instance(LoggerInterface::class, $logger = new TestLogger());

            $handler = $this->newErrorHandler();

            $handler->transformToResponse($e = new ReportableException('TestMessage'));

            $logger->assertHasLogEntry('TestMessage', ['user_id' => 10, 'exception' => $e]);
            $this->assertContains('TestMessage', $GLOBALS['test']['log']);


        }

        /** @test */
        public function propagation_to_the_default_logger_can_be_stopped()
        {

            $this->container->instance(LoggerInterface::class, $logger = new TestLogger());

            $handler = $this->newErrorHandler();

            $handler->transformToResponse(new StopPropagationException('TestMessage'));

            $logger->assertHasNoLogEntries();
            $this->assertContains('TestMessage', $GLOBALS['test']['log']);

        }

        /** @test */
        public function logging_dependencies_are_resolved_from_the_container()
        {

            $this->assertEmpty($GLOBALS['test']['log']);

            $handler = $this->newErrorHandler();

            $handler->transformToResponse(new LogExceptionWithFooDependency('TestMessage'));

            $this->assertContains('TestMessage:foo', $GLOBALS['test']['log']);

        }

        /** @test */
        public function exceptions_can_be_ignored_for_reporting_from_a_child_class()
        {

            $this->container->instance(LoggerInterface::class, $logger = new TestLogger());
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
        public function the_global_context_can_be_overwritten_from_a_child_class()
        {

            $this->container->instance(LoggerInterface::class, $logger = new TestLogger());
            $this->container->instance(ProductionErrorHandler::class, CustomProductionErrorHandler::class);

            $handler = $this->newErrorHandler();

            $handler->transformToResponse($e = new Exception('Foobar'));

            $logger->assertHasLogEntry('Foobar', ['foo' => 'bar', 'exception' => $e]);

        }

        private function newErrorHandler(bool $is_ajax = false) : ProductionErrorHandler
        {

            return ErrorHandlerFactory::make($this->container, false, $is_ajax);

        }


    }


    class ContextException extends Exception
    {

        public function context() : array
        {

            return ['foo' => 'bar'];

        }

    }


    class ReportableException extends Exception
    {

        public function report()
        {

            $GLOBALS['test']['log'][] = $this->getMessage();

        }

    }


    class StopPropagationException extends Exception
    {

        public function report() : bool
        {

            $GLOBALS['test']['log'][] = $this->getMessage();

            return false;

        }

    }


    class LogExceptionWithFooDependency extends Exception
    {

        public function report(Foo $foo)
        {

            $GLOBALS['test']['log'][] = $this->getMessage().':'.$foo->foo;

        }


    }


    class CustomProductionErrorHandler extends ProductionErrorHandler
    {

        protected $dont_report = [
            ReportableException::class,
        ];

        protected function globalContext() : array
        {

            return ['foo' => 'bar'];

        }

    }