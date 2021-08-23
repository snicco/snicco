<?php

declare(strict_types=1);

namespace Tests\unit\Exceptions;

use Exception;
use Tests\UnitTest;
use Psr\Log\LogLevel;
use Snicco\Support\WP;
use Tests\stubs\TestLogger;
use Tests\stubs\TestRequest;
use Snicco\Http\Psr7\Request;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Tests\stubs\TestViewFactory;
use Tests\helpers\AssertsResponse;
use Tests\helpers\CreateUrlGenerator;
use Tests\fixtures\TestDependencies\Foo;
use Tests\helpers\CreateRouteCollection;
use Tests\helpers\CreateDefaultWpApiMocks;
use Snicco\Contracts\ViewFactoryInterface;
use Snicco\ExceptionHandling\ProductionExceptionHandler;

class ProductionExceptionHandlerLoggingTest extends UnitTest
{
    
    use AssertsResponse;
    use CreateUrlGenerator;
    use CreateRouteCollection;
    use CreateDefaultWpApiMocks;
    
    private ContainerAdapter $container;
    
    private Request $request;
    
    private TestLogger $test_logger;
    
    /** @test */
    public function exceptions_are_logged_with_the_default_logger_if_the_exception_doesnt_have_a_report_method()
    {
        
        $handler = $this->newErrorHandler();
        
        $handler->report(new Exception('Foobar'), $this->request);
        
        $this->test_logger->assertHasLogLevelEntry(LogLevel::ERROR, 'Foobar');
        
    }
    
    /** @test */
    public function custom_log_levels_can_be_provided_to_the_report_function_at_runtime()
    {
        
        $handler = $this->newErrorHandler();
        
        $handler->report(new Exception('Foobar'), $this->request, LogLevel::CRITICAL);
        
        $this->test_logger->assertHasLogLevelEntry(LogLevel::CRITICAL, 'Foobar');
        
    }
    
    /** @test */
    public function the_current_user_id_and_email_is_included_in_the_exception_context()
    {
        
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new Exception('Foobar'), $this->request);
        
        $this->test_logger->assertHasLogEntry(
            'Foobar',
            ['user_id' => 10, 'user_email' => 'c@web.de', 'exception' => $e]
        );
        
    }
    
    /** @test */
    public function the_user_id_is_not_included_if_there_is_nobody_logged_in()
    {
        
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new Exception('Foobar'), $this->request);
        
        $this->test_logger->assertHasLogEntry('Foobar', ['exception' => $e]);
        
    }
    
    /** @test */
    public function exception_context_is_included_in_the_error_log_message_if_the_exception_has_a_context_method()
    {
        
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new ContextException('TestMessage'), $this->request);
        
        $this->test_logger->assertHasLogEntry('TestMessage', [
            'foo' => 'bar',
            'exception' => $e,
        ]);
        
    }
    
    /** @test */
    public function the_exception_object_is_included_in_the_log_context()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new Exception('Foobar'), $this->request);
        
        $this->test_logger->assertHasLogEntry('Foobar', ['exception' => $e]);
        
    }
    
    /** @test */
    public function exception_objects_can_have_custom_reporting_logic()
    {
        
        $this->assertEmpty($GLOBALS['test']['log']);
        
        $handler = $this->newErrorHandler();
        
        $handler->report(new ReportableException('foobarlog'), $this->request);
        
        $this->assertContains('foobarlog', $GLOBALS['test']['log']);
        
    }
    
    /** @test */
    public function exceptions_are_still_written_to_the_default_logger_after_custom_exceptions()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new ReportableException('TestMessage'), $this->request);
        
        $this->test_logger->assertHasLogEntry('TestMessage', ['exception' => $e]);
        $this->assertContains('TestMessage', $GLOBALS['test']['log']);
        
    }
    
    /** @test */
    public function custom_reporting_behaviour_can_be_defined_for_all_exceptions()
    {
        
        $this->assertEmpty($GLOBALS['test']['log']);
        
        $handler = $this->newErrorHandler();
        
        $handler->reportable(function (ReportableException $exception, Request $request, Foo $foo) {
            
            $GLOBALS['test']['log'][] =
                $exception->getMessage().'-'.$request->getAttribute('foo').'-'.$foo->foo;
            
        });
        
        $handler->reportable(function (ReportableException $e) {
            
            $GLOBALS['test']['log'][] = $e->getMessage().'-WITH_SHORTHAND_VARNAME';
            
        });
        
        $handler->report(
            new ReportableException('Error Message'),
            $this->request->withAttribute('foo', 'REQUEST')
        );
        
        $this->assertContains('Error Message-REQUEST-foo', $GLOBALS['test']['log']);
        $this->assertContains('Error Message-WITH_SHORTHAND_VARNAME', $GLOBALS['test']['log']);
        // This comes from the ReportableException itself
        $this->assertContains('Error Message', $GLOBALS['test']['log']);
        
    }
    
    /** @test */
    public function returning_false_from_custom_error_reporting_will_stop_the_propagation_to_the_default_logger()
    {
        
        $this->assertEmpty($GLOBALS['test']['log']);
        
        $handler = $this->newErrorHandler();
        
        $handler->reportable(function (ReportableException $exception, Request $request, Foo $foo) {
            
            $GLOBALS['test']['log'][] =
                $exception->getMessage().'-'.$request->getAttribute('foo').'-'.$foo->foo;
            return false;
            
        });
        
        $handler->report(
            new ReportableException('Error Message'),
            $this->request->withAttribute('foo', 'REQUEST')
        );
        
        $this->assertContains('Error Message-REQUEST-foo', $GLOBALS['test']['log']);
        // This comes from the ReportableException itself
        $this->assertNotContains('Error Message', $GLOBALS['test']['log']);
        
    }
    
    /** @test */
    public function propagation_to_the_default_logger_can_be_stopped()
    {
        
        $handler = $this->newErrorHandler();
        
        $handler->report(new StopPropagationException('TestMessage'), $this->request);
        
        $this->test_logger->assertHasNoLogEntries();
        $this->assertContains('TestMessage', $GLOBALS['test']['log']);
        
    }
    
    /** @test */
    public function logging_dependencies_are_resolved_from_the_container()
    {
        
        $this->assertEmpty($GLOBALS['test']['log']);
        
        $handler = $this->newErrorHandler();
        
        $handler->report(
            new LogExceptionWithFooDependency('TestMessage'),
            $this->request
        );
        
        $this->assertContains('TestMessage:foo', $GLOBALS['test']['log']);
        
    }
    
    /** @test */
    public function exceptions_can_be_ignored_for_reporting_from_a_child_class()
    {
        
        $handler = new CustomProductionErrorHandler(
            $this->container,
            $this->test_logger = new TestLogger(),
            $this->container[ResponseFactory::class]
        );
        
        $this->assertEmpty($GLOBALS['test']['log']);
        
        $handler->report(new ReportableException('foo'), $this->request);
        
        $this->test_logger->assertHasNoLogEntries();
        $this->assertEmpty($GLOBALS['test']['log']);
        
    }
    
    /** @test */
    public function the_global_context_can_be_overwritten_from_a_child_class()
    {
        
        $handler = new CustomProductionErrorHandler(
            $this->container,
            $this->test_logger = new TestLogger(),
            $this->container[ResponseFactory::class]
        );
        
        $handler->report($e = new Exception('Foobar'), $this->request);
        
        $this->test_logger->assertHasLogEntry('Foobar', ['foo' => 'bar', 'exception' => $e]);
        
    }
    
    protected function beforeTestRun()
    {
        $this->container = $this->createContainer();
        $this->request = TestRequest::from('GET', 'foo');
        $this->container->instance(ResponseFactory::class, $this->createResponseFactory());
        $this->container->instance(ViewFactoryInterface::class, new TestViewFactory());
        WP::shouldReceive('userId')->andReturn(10);
        WP::shouldReceive('isUserLoggedIn')->andReturnTrue()->byDefault();
        WP::shouldReceive('currentUser')->andReturnUsing(function () {
            
            $user = \Mockery::mock(\WP_User::class);
            $user->user_email = 'c@web.de';
            return $user;
        });
        $GLOBALS['test']['log'] = [];
        
    }
    
    protected function tearDown() :void
    {
        \Mockery::close();
        WP::reset();
        parent::tearDown();
    }
    
    private function newErrorHandler() :ProductionExceptionHandler
    {
        
        return new ProductionExceptionHandler(
            $this->container,
            $this->test_logger = new TestLogger(),
            $this->container[ResponseFactory::class],
            null
        );
        
    }
    
}

class ContextException extends Exception
{
    
    public function context() :array
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
    
    public function report() :bool
    {
        
        $GLOBALS['test']['log'][] = $this->getMessage();
        
        return ProductionExceptionHandler::STOP_REPORTING;
        
    }
    
}

class LogExceptionWithFooDependency extends Exception
{
    
    public function report(Foo $foo)
    {
        
        $GLOBALS['test']['log'][] = $this->getMessage().':'.$foo->foo;
        
    }
    
}

class CustomProductionErrorHandler extends ProductionExceptionHandler
{
    
    protected array $dont_report = [
        ReportableException::class,
    ];
    
    protected function globalContext(Request $request) :array
    {
        return ['foo' => 'bar'];
    }
    
}