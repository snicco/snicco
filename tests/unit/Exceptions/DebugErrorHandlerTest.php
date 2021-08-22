<?php

declare(strict_types=1);

namespace Tests\unit\Exceptions;

use Tests\UnitTest;
use Snicco\Events\Event;
use Tests\stubs\TestRequest;
use Snicco\Http\Psr7\Request;
use Tests\stubs\TestException;
use Contracts\ContainerAdapter;
use Tests\helpers\AssertsResponse;
use Snicco\Factories\ErrorHandlerFactory;
use Snicco\Events\UnrecoverableExceptionHandled;
use Snicco\ExceptionHandling\DebugExceptionHandler;

class DebugErrorHandlerTest extends UnitTest
{
    
    use AssertsResponse;
    
    private Request          $request;
    
    private ContainerAdapter $container;
    
    /** @test */
    public function exceptions_are_rendered_with_whoops()
    {
        
        $handler = $this->newErrorHandler();
        
        $exception = new TestException('Whoops Exception');
        
        ob_start();
        $handler->transformToResponse($exception, $this->request);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Whoops Exception', $output);
        
        Event::assertDispatchedTimes(UnrecoverableExceptionHandled::class, 1);
        
    }
    
    /** @test */
    public function debug_data_is_provided_in_the_json_response_for_request_that_expect_json()
    {
        
        $handler = $this->newErrorHandler(true);
        
        $exception = new TestException('Whoops Ajax Exception');
        
        ob_start();
        $handler->transformToResponse(
            $exception,
            $this->request->withAddedHeader('Accept', 'application/json')
        );
        $response = ob_get_clean();
        
        $output = json_decode($response, true)['error'];
        $this->assertSame('Tests\stubs\TestException', $output['type']);
        $this->assertSame('Whoops Ajax Exception', $output['message']);
        $this->assertArrayHasKey('code', $output);
        $this->assertArrayHasKey('trace', $output);
        $this->assertArrayHasKey('file', $output);
        $this->assertArrayHasKey('line', $output);
        $this->assertArrayHasKey('trace', $output);
        
        Event::assertDispatchedTimes(UnrecoverableExceptionHandled::class, 1);
        
    }
    
    /** @test */
    public function json_data_can_be_provided_even_if_the_exception_occurred_outside_the_routing_flow()
    {
        
        $handler = $this->newErrorHandler(true);
        
        $this->container->instance(
            Request::class,
            $this->request->withAddedHeader('Accept', 'application/json')
        );
        
        ob_start();
        
        try {
            
            throw new TestException('Whoops Ajax Exception');
            
        } catch (TestException $e) {
            
            $handler->handleException($e);
            
            $response = ob_get_clean();
            
            $output = json_decode($response, true)['error'];
            $this->assertSame('Tests\stubs\TestException', $output['type']);
            $this->assertSame('Whoops Ajax Exception', $output['message']);
            $this->assertArrayHasKey('code', $output);
            $this->assertArrayHasKey('trace', $output);
            $this->assertArrayHasKey('file', $output);
            $this->assertArrayHasKey('line', $output);
            $this->assertArrayHasKey('trace', $output);
            
            $handler->unregister();
            Event::assertDispatchedTimes(UnrecoverableExceptionHandled::class, 1);
            
        }
        
    }
    
    protected function beforeTestRun()
    {
        
        $this->container = $this->createContainer();
        Event::make($this->container);
        Event::fake();
        $this->request = TestRequest::from('GET', 'foo');
        
    }
    
    protected function afterSetup() :void
    {
        
        Event::setInstance(null);
        
    }
    
    private function newErrorHandler() :DebugExceptionHandler
    {
        
        return ErrorHandlerFactory::make(
            $this->container,
            true,
        );
        
    }
    
}
