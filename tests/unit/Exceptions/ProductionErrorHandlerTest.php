<?php

declare(strict_types=1);

namespace Tests\unit\Exceptions;

use Mockery;
use Exception;
use Throwable;
use Tests\UnitTest;
use Snicco\Support\WP;
use Psr\Log\NullLogger;
use Snicco\Events\Event;
use Psr\Log\LoggerInterface;
use Tests\stubs\HeaderStack;
use Tests\stubs\TestRequest;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Tests\stubs\TestException;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Tests\helpers\AssertsResponse;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateRouteCollection;
use Snicco\Factories\ErrorHandlerFactory;
use Tests\helpers\CreateDefaultWpApiMocks;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Events\UnrecoverableExceptionHandled;
use Snicco\ExceptionHandling\Exceptions\HttpException;
use Snicco\ExceptionHandling\ProductionExceptionHandler;

class ProductionErrorHandlerTest extends UnitTest
{
    
    use AssertsResponse;
    use CreateUrlGenerator;
    use CreateRouteCollection;
    use CreateDefaultWpApiMocks;
    
    private ContainerAdapter $container;
    
    private Request          $request;
    
    /** @test */
    public function inside_the_routing_flow_the_exceptions_get_transformed_into_response_objects()
    {
        
        $handler = $this->newErrorHandler();
        
        $response =
            $handler->transformToResponse(new TestException('Sensitive Info'), $this->request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertOutput(
            'VIEW:500,CONTEXT:[status_code=>500,message=>Something went wrong.]',
            $response
        );
        $this->assertStatusCode(500, $response);
        Event::assertNotDispatched(UnrecoverableExceptionHandled::class);
        
    }
    
    /** @test */
    public function outside_the_routing_flow_exceptions_will_lead_to_script_termination()
    {
        
        $handler = $this->newErrorHandler();
        
        $this->container->instance(Request::class, $this->request);
        
        $handler->handleException(new TestException('Sensitive Info'));
        
        Event::assertDispatched(UnrecoverableExceptionHandled::class);
        $this->expectOutputString(
            'VIEW:500,CONTEXT:[status_code=>500,message=>Something went wrong.]'
        );
        
    }
    
    /** @test */
    public function the_response_will_be_sent_as_json_if_the_request_expects_json()
    {
        
        $handler = $this->newErrorHandler();
        
        $request = $this->request->withAddedHeader('Accept', 'application/json');
        
        $response = $handler->transformToResponse(new TestException('Sensitive Info'), $request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStatusCode(500, $response);
        $this->assertContentType('application/json', $response);
        $this->assertSame(
            ['message' => 'Something went wrong.'],
            json_decode($response->getBody()->__toString(), true)
        );
        Event::assertNotDispatched(UnrecoverableExceptionHandled::class);
        
    }
    
    /** @test */
    public function outside_the_routing_flow_responses_are_send_as_json_if_expected_by_the_request()
    {
        
        $handler = $this->newErrorHandler();
        
        $this->container->instance(
            Request::class,
            $this->request->withAddedHeader('Accept', 'application/json')
        );
        
        $handler->handleException(new TestException('Sensitive Info'));
        
        Event::assertDispatched(UnrecoverableExceptionHandled::class);
        HeaderStack::assertHasStatusCode(500);
        HeaderStack::assertHas('Content-Type', 'application/json');
        $this->expectOutputString(json_encode(['message' => 'Something went wrong.']));
        
    }
    
    /** @test */
    public function an_unspecified_exception_gets_converted_into_a_500_internal_server_error()
    {
        
        $handler = $this->newErrorHandler();
        
        $response =
            $handler->transformToResponse(new TestException('Sensitive Info'), $this->request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStatusCode(500, $response);
        $this->assertContentType('text/html', $response);
        $this->assertOutput(
            'VIEW:500,CONTEXT:[status_code=>500,message=>Something went wrong.]',
            $response
        );
        
        Event::assertNotDispatched(UnrecoverableExceptionHandled::class);
        
    }
    
    /** @test */
    public function an_exception_can_have_custom_rendering_logic()
    {
        
        $handler = $this->newErrorHandler();
        
        $response = $handler->transformToResponse(new RenderableException(), $this->request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStatusCode(500, $response);
        $this->assertContentType('text/html', $response);
        $this->assertOutput('Foo', $response);
        
        Event::assertNotDispatched(UnrecoverableExceptionHandled::class);
        
    }
    
    /** @test */
    public function renderable_exceptions_MUST_return_a_response_object()
    {
        
        $handler = $this->newErrorHandler();
        
        $response = $handler->transformToResponse(new WrongReturnTypeException(), $this->request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStatusCode(500, $response);
        $this->assertContentType('text/html', $response);
        
        // We rethrow the exception.
        $this->assertOutput(
            'VIEW:500,CONTEXT:[status_code=>500,message=>Something went wrong.]',
            $response
        );
        
        Event::assertNotDispatched(UnrecoverableExceptionHandled::class);
        
    }
    
    /** @test */
    public function renderable_exceptions_receive_the_current_request_and_a_response_factory_instance()
    {
        
        $handler = $this->newErrorHandler();
        
        $response = $handler->transformToResponse(
            new ExceptionWithDependencyInjection(),
            $this->request->withAttribute('foo', 'bar')
        );
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStatusCode(403, $response);
        $this->assertContentType('text/html', $response);
        $this->assertOutput('bar', $response);
        
        Event::assertNotDispatched(UnrecoverableExceptionHandled::class);
        
    }
    
    /** @test */
    public function a_custom_error_handler_can_replace_the_default_response_object()
    {
        
        $this->container->instance(ProductionExceptionHandler::class, CustomErrorHandler::class);
        
        $handler = $this->newErrorHandler();
        
        $response = $handler->transformToResponse(
            new Exception('Sensitive Data nobody should read'),
            $this->request
        );
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStatusCode(500, $response);
        $this->assertContentType('text/html', $response);
        $this->assertOutput(
            'VIEW:500,CONTEXT:[status_code=>500,message=>Custom Error Message]',
            $response
        );
        
        Event::assertNotDispatched(UnrecoverableExceptionHandled::class);
        
    }
    
    /** @test */
    public function exception_rendering_can_be_overwritten_for_every_exception()
    {
        
        $handler = $this->newErrorHandler();
        
        $handler->renderable(
            function (RenderableException $e, Request $request, ResponseFactory $response_factory) {
                
                return $response_factory->html('FOO_CUSTOMIZED')->withStatus(403);
                
            }
        );
        
        // Nothing matching
        $response = $handler->transformToResponse(new Exception('Error'), $this->request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStatusCode(500, $response);
        $this->assertContentType('text/html', $response);
        $this->assertOutput(
            'VIEW:500,CONTEXT:[status_code=>500,message=>Something went wrong.]',
            $response
        );
        
        $response = $handler->transformToResponse(new RenderableException('Error'), $this->request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStatusCode(403, $response);
        $this->assertContentType('text/html', $response);
        $this->assertOutput('FOO_CUSTOMIZED', $response);
        
    }
    
    protected function beforeTestRun()
    {
        
        Event::make($this->container = $this->createContainer());
        Event::fake();
        $this->container->instance(StreamFactoryInterface::class, $this->psrStreamFactory());
        $this->container->instance(
            ProductionExceptionHandler::class,
            ProductionExceptionHandler::class
        );
        $this->container->instance(ResponseFactory::class, $this->createResponseFactory());
        WP::setFacadeContainer($this->createContainer());
        $this->request = TestRequest::from('GET', 'foo');
        $this->container->instance(LoggerInterface::class, new NullLogger());
        
    }
    
    protected function beforeTearDown()
    {
        
        WP::reset();
        Event::setInstance(null);
        Mockery::close();
        HeaderStack::reset();
        
    }
    
    private function newErrorHandler() :ProductionExceptionHandler
    {
        
        return ErrorHandlerFactory::make($this->container, false);
        
    }
    
}

class RenderableException extends Exception
{
    
    public function render(ResponseFactory $factory)
    {
        
        return $factory->html('Foo')->withStatus(500);
        
    }
    
}

class WrongReturnTypeException extends Exception
{
    
    public function render()
    {
        
        return 'foo';
        
    }
    
}

class ExceptionWithDependencyInjection extends Exception
{
    
    public function render(Request $request, ResponseFactory $response_factory)
    {
        
        return $response_factory
            ->html($request->getAttribute('foo'))
            ->withStatus(403);
        
    }
    
}

class CustomErrorHandler extends ProductionExceptionHandler
{
    
    protected function toHttpException(Throwable $e, Request $request) :HttpException
    {
        
        return (new HttpException(500, 'Custom Error Message'))->withMessageForUsers(
            'Custom Error Message'
        );
        
    }
    
}

