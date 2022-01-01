<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling;

use Mockery;
use Exception;
use Psr\Log\LogLevel;
use Snicco\Core\Support\WP;
use Psr\Log\Test\TestLogger;
use Snicco\Core\Http\Psr7\Request;
use Tests\Codeception\shared\UnitTest;
use Snicco\Core\Shared\ContainerAdapter;
use Snicco\Core\Contracts\ResponseFactory;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\Core\ExceptionHandling\ProductionExceptionHandler;
use Tests\Codeception\shared\helpers\CreateDefaultWpApiMocks;

class ProductionExceptionHandlerLoggingTest extends UnitTest
{
    
    use CreateDefaultWpApiMocks;
    use CreateContainer;
    
    private ContainerAdapter $container;
    private Request          $request;
    private TestLogger       $test_logger;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->container = $this->createContainer();
        $this->container->instance(Foo::class, new Foo());
        $this->request = TestRequest::from('GET', 'foo');
        WP::shouldReceive('userId')->andReturn(10)->byDefault();
        WP::shouldReceive('isUserLoggedIn')->andReturnTrue()->byDefault();
        $GLOBALS['test']['log'] = [];
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
        WP::reset();
    }
    
    /** @test */
    public function exceptions_are_logged_with_the_default_logger_if_the_exception_doesnt_have_a_report_method()
    {
        $handler = $this->newErrorHandler();
        
        $handler->report(new Exception('Foobar'), $this->request);
        
        $this->assertTrue($this->test_logger->hasErrorThatContains('Foobar'));
    }
    
    /** @test */
    public function custom_log_levels_can_be_provided_to_the_report_function_at_runtime()
    {
        $handler = $this->newErrorHandler();
        
        $handler->report(new Exception('Foobar'), $this->request, LogLevel::CRITICAL);
        
        $this->assertTrue($this->test_logger->hasCriticalThatContains('Foobar'));
    }
    
    /** @test */
    public function the_current_user_id_and_email_is_included_in_the_exception_context()
    {
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new Exception('Foobar'), $this->request);
        
        $record = $this->test_logger->recordsByLevel[LogLevel::ERROR][0];
        
        $this->assertSame('Foobar', $record['message']);
        $this->assertSame([
            'user_id' => 10,
            'exception' => $e,
        ], $record['context']);
    }
    
    /** @test */
    public function the_user_id_is_not_included_if_there_is_nobody_logged_in()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new Exception('Foobar'), $this->request);
        
        $record = $this->test_logger->recordsByLevel[LogLevel::ERROR][0];
        
        $this->assertSame('Foobar', $record['message']);
        $this->assertSame([
            'exception' => $e,
        ], $record['context']);
    }
    
    /** @test */
    public function exception_context_is_included_in_the_error_log_message_if_the_exception_has_a_context_method()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new fixtures\ContextException('TestMessage'), $this->request);
        
        $record = $this->test_logger->recordsByLevel[LogLevel::ERROR][0];
        
        $this->assertSame('TestMessage', $record['message']);
        $this->assertSame([
            'foo' => 'bar',
            'exception' => $e,
        ], $record['context']);
    }
    
    /** @test */
    public function exception_objects_can_have_custom_reporting_logic()
    {
        $this->assertEmpty($GLOBALS['test']['log']);
        
        $handler = $this->newErrorHandler();
        
        $handler->report(new fixtures\ReportableException('foobarlog'), $this->request);
        
        $this->assertContains('foobarlog', $GLOBALS['test']['log']);
    }
    
    /** @test */
    public function exceptions_are_still_written_to_the_default_logger_after_custom_exceptions()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new fixtures\ReportableException('TestMessage'), $this->request);
        
        $this->assertTrue($this->test_logger->hasErrorRecords());
        $this->assertContains('TestMessage', $GLOBALS['test']['log']);
    }
    
    /** @test */
    public function custom_reporting_behaviour_can_be_defined_for_all_exceptions()
    {
        $this->assertEmpty($GLOBALS['test']['log']);
        
        $handler = $this->newErrorHandler();
        
        $handler->reportable(
            function (fixtures\ReportableException $exception, Request $request, Foo $foo) {
                $GLOBALS['test']['log'][] =
                    $exception->getMessage().'-'.$request->getAttribute('foo').'-'.$foo->foo;
            }
        );
        
        $handler->reportable(function (fixtures\ReportableException $e) {
            $GLOBALS['test']['log'][] = $e->getMessage().'-WITH_SHORTHAND_VARNAME';
        });
        
        $handler->report(
            new fixtures\ReportableException('Error Message'),
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
        
        $handler->reportable(
            function (fixtures\ReportableException $exception, Request $request, Foo $foo) {
                $GLOBALS['test']['log'][] =
                    $exception->getMessage().'-'.$request->getAttribute('foo').'-'.$foo->foo;
                return false;
            }
        );
        
        $handler->report(
            new fixtures\ReportableException('Error Message'),
            $this->request->withAttribute('foo', 'REQUEST')
        );
        
        $this->assertContains('Error Message-REQUEST-foo', $GLOBALS['test']['log']);
        
        $this->assertFalse($this->test_logger->hasErrorRecords());
    }
    
    /** @test */
    public function propagation_to_the_default_logger_can_be_stopped()
    {
        $handler = $this->newErrorHandler();
        
        $handler->report(new fixtures\StopPropagationException('TestMessage'), $this->request);
        
        $this->assertFalse($this->test_logger->hasErrorRecords());
        $this->assertContains('TestMessage', $GLOBALS['test']['log']);
    }
    
    /** @test */
    public function logging_dependencies_are_resolved_from_the_container()
    {
        $this->assertEmpty($GLOBALS['test']['log']);
        
        $handler = $this->newErrorHandler();
        
        $handler->report(
            new fixtures\LogExceptionWithFooDependency('TestMessage'),
            $this->request
        );
        
        $this->assertContains('TestMessage:foo', $GLOBALS['test']['log']);
    }
    
    /** @test */
    public function exceptions_can_be_ignored_for_reporting_from_a_child_class()
    {
        $handler = new fixtures\CustomExceptionHandler(
            $this->container,
            $this->test_logger = new TestLogger(),
            Mockery::mock(ResponseFactory::class)
        );
        
        $this->assertEmpty($GLOBALS['test']['log']);
        
        $handler->report(new fixtures\ReportableException('foo'), $this->request);
        
        $this->assertFalse($this->test_logger->hasErrorRecords());
        $this->assertEmpty($GLOBALS['test']['log']);
    }
    
    /** @test */
    public function the_global_context_can_be_overwritten_from_a_child_class()
    {
        $handler = new fixtures\CustomExceptionHandler(
            $this->container,
            $this->test_logger = new TestLogger(),
            Mockery::mock(ResponseFactory::class),
        );
        
        $handler->report($e = new Exception('Foobar'), $this->request);
        
        $record = $this->test_logger->recordsByLevel[LogLevel::ERROR][0];
        
        $this->assertSame('Foobar', $record['message']);
        $this->assertSame([
            'foo' => 'bar',
            'exception' => $e,
        ], $record['context']);
    }
    
    private function newErrorHandler() :ProductionExceptionHandler
    {
        return new ProductionExceptionHandler(
            $this->container,
            $this->test_logger = new TestLogger(),
            Mockery::mock(ResponseFactory::class),
            null
        );
    }
    
}

