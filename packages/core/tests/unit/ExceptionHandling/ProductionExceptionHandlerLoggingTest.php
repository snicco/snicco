<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling;

use Mockery;
use WP_User;
use Exception;
use Psr\Log\LogLevel;
use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\Shared\ContainerAdapter;
use Tests\Codeception\shared\UnitTest;
use Tests\Core\fixtures\TestDoubles\TestLogger;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Tests\Codeception\shared\TestDependencies\Foo;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\ExceptionHandling\ProductionExceptionHandler;
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
        $this->request = TestRequest::from('GET', 'foo');
        WP::shouldReceive('userId')->andReturn(10);
        WP::shouldReceive('isUserLoggedIn')->andReturnTrue()->byDefault();
        WP::shouldReceive('currentUser')->andReturnUsing(function () {
            $user = Mockery::mock(WP_User::class);
            $user->user_email = 'c@web.de';
            return $user;
        });
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
        
        $handler->report($e = new fixtures\ContextException('TestMessage'), $this->request);
        
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
        
        $handler->report(new fixtures\ReportableException('foobarlog'), $this->request);
        
        $this->assertContains('foobarlog', $GLOBALS['test']['log']);
    }
    
    /** @test */
    public function exceptions_are_still_written_to_the_default_logger_after_custom_exceptions()
    {
        WP::shouldReceive('isUserLoggedIn')->andReturnFalse();
        
        $handler = $this->newErrorHandler();
        
        $handler->report($e = new fixtures\ReportableException('TestMessage'), $this->request);
        
        $this->test_logger->assertHasLogEntry('TestMessage', ['exception' => $e]);
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
        // This comes from the ReportableException itself
        $this->assertNotContains('Error Message', $GLOBALS['test']['log']);
    }
    
    /** @test */
    public function propagation_to_the_default_logger_can_be_stopped()
    {
        $handler = $this->newErrorHandler();
        
        $handler->report(new fixtures\StopPropagationException('TestMessage'), $this->request);
        
        $this->test_logger->assertHasNoLogEntries();
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
        
        $this->test_logger->assertHasNoLogEntries();
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
        
        $this->test_logger->assertHasLogEntry('Foobar', ['foo' => 'bar', 'exception' => $e]);
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

