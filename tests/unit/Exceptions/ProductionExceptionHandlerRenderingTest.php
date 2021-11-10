<?php

declare(strict_types=1);

namespace Tests\unit\Exceptions;

use Mockery;
use Exception;
use Throwable;
use Whoops\Run;
use Tests\UnitTest;
use RuntimeException;
use Snicco\Support\WP;
use Psr\Log\NullLogger;
use Illuminate\Support\Arr;
use Tests\stubs\TestRequest;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Tests\stubs\TestException;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Snicco\Testing\TestResponse;
use Tests\stubs\TestViewFactory;
use Snicco\Application\Application;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateRouteCollection;
use Snicco\Contracts\ViewFactoryInterface;
use Snicco\ExceptionHandling\WhoopsHandler;
use Snicco\ExceptionHandling\Exceptions\HttpException;
use Snicco\ExceptionHandling\Exceptions\ViewException;
use Snicco\ExceptionHandling\ProductionExceptionHandler;
use Snicco\ExceptionHandling\Exceptions\ErrorViewException;

class ProductionExceptionHandlerRenderingTest extends UnitTest
{
    
    use CreateUrlGenerator;
    use CreateRouteCollection;
    
    private ContainerAdapter $container;
    private Request          $request;
    
    /** @test */
    public function exceptions_are_transformed_to_response_objects()
    {
        
        $handler = $this->newErrorHandler();
        
        $response = new TestResponse(
            $handler->toHttpResponse(new TestException('Sensitive Info'), $this->request)
        );
        
        $this->assertInstanceOf(Response::class, $response->psr_response);
        $response->assertSeeHtml(
            'VIEW:framework.errors.500,CONTEXT:[status_code=>500,message=>Something went wrong.]'
        );
        $response->assertDontSee('Sensitive Info');
        $response->assertStatus(500);
        $response->assertHeader('content-type', 'text/html');
        
    }
    
    /** @test */
    public function the_response_will_be_sent_as_json_if_the_request_expects_json()
    {
        
        $handler = $this->newErrorHandler();
        $request = $this->request->withAddedHeader('Accept', 'application/json');
        
        $response = new TestResponse(
            $handler->toHttpResponse(new TestException('Sensitive Info'), $request)
        );
        
        $response->assertIsJson();
        $response->assertStatus(500);
        $response->assertExactJson(['message' => 'Something went wrong.']);
        
    }
    
    /** @test */
    public function in_production_the_a_custom_json_message_is_rendered_if_present_on_exception()
    {
        
        $e = new HttpException(403, 'Sensible Info');
        $e->withMessageForUsers('Sorry, something went wrong');
        $e->withJsonMessageForUsers('Sorry, this didnt work');
        
        $handler = $this->newErrorHandler();
        $request = $this->request->withAddedHeader('Accept', 'application/json');
        
        $response = new TestResponse(
            $handler->toHttpResponse($e, $request)
        );
        
        $response->assertIsJson()->assertForbidden();
        $response->assertExactJson(['message' => 'Sorry, this didnt work']);
        
    }
    
    /** @test */
    public function a_non_http_exception_always_gets_transformed_into_generic_error_screen()
    {
        
        $handler = $this->newErrorHandler();
        
        $response = new TestResponse(
            $handler->toHttpResponse(new RuntimeException('Sensitive Info'), $this->request)
        );
        
        $this->assertInstanceOf(Response::class, $response->psr_response);
        $response->assertSeeHtml(
            'VIEW:framework.errors.500,CONTEXT:[status_code=>500,message=>Something went wrong.]'
        );
        $response->assertDontSee('Sensitive Info');
        $response->assertStatus(500);
        $response->assertHeader('content-type', 'text/html');
        
    }
    
    /** @test */
    public function an_exception_can_have_custom_rendering_logic()
    {
        
        $handler = $this->newErrorHandler();
        
        $response = new TestResponse(
            $handler->toHttpResponse(new RenderableException('Sensitive Info'), $this->request)
        );
        
        $this->assertInstanceOf(Response::class, $response->psr_response);
        $response->assertSee('Foo');
        $response->assertDontSee('Sensitive Info');
        $response->assertStatus(500);
        $response->assertHeader('content-type', 'text/html');
        
    }
    
    /** @test */
    public function renderable_exceptions_must_return_response_object()
    {
        
        $handler = $this->newErrorHandler();
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Return value of %s::render() has to be an instance of [Snicco\Http\Psr7\Response]",
                ReturnsStringException::class
            )
        );
        
        $handler->toHttpResponse(new ReturnsStringException('Sensitive Info'), $this->request);
        
    }
    
    /** @test */
    public function renderable_exceptions_receive_the_current_request_and_a_response_factory_instance()
    {
        
        $handler = $this->newErrorHandler();
        
        $response = new TestResponse(
            $handler->toHttpResponse(
                new ExceptionWithDependencyInjection(),
                $this->request->withAttribute('foo', 'bar')
            )
        );
        
        $response->assertStatus(403);
        $response->assertHeader('content-type', 'text/html');
        $response->assertSee('bar');
        
    }
    
    /** @test */
    public function a_custom_error_handler_can_replace_the_default_response_object()
    {
        
        $handler = new CustomErrorHandler(
            $this->container,
            new NullLogger(),
            $this->container[ResponseFactory::class]
        );
        
        $response = new TestResponse(
            $handler->toHttpResponse(
                new Exception('Sensitive Data nobody should read'),
                $this->request
            )
        );
        
        $response->assertStatus(500);
        $response->assertSeeHtml(
            'VIEW:framework.errors.500,CONTEXT:[status_code=>500,message=>Custom Error Message]'
        );
        
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
        
        // No custom renderer for general Exceptions
        $response =
            new TestResponse($handler->toHttpResponse(new Exception('Error'), $this->request));
        $response->assertStatus(500);
        $response->assertSeeHtml(
            'VIEW:framework.errors.500,CONTEXT:[status_code=>500,message=>Something went wrong.]'
        );
        
        $response = new TestResponse(
            $handler->toHttpResponse(new RenderableException('Error'), $this->request)
        );
        $response->assertStatus(403);
        $response->assertSeeHtml('FOO_CUSTOMIZED');
        
    }
    
    /** @test */
    public function an_exception_while_trying_to_render_a_default_error_view_will_throw_an_special_error_view_exception()
    {
        
        $view_factory = Mockery::mock(ViewFactoryInterface::class);
        $view_factory->shouldReceive('render')->once()->andThrow(
            $view_exception = new ViewException()
        );
        $this->container->instance(ViewFactoryInterface::class, $view_factory);
        
        $handler = $this->newErrorHandler();
        
        try {
            $response = new TestResponse(
                $handler->toHttpResponse(
                    new Exception('Sensitive Info'),
                    $this->request
                )
            );
        } catch (ErrorViewException $e) {
            
            $this->assertSame($e->getPrevious(), $view_exception);
            
            $response = new TestResponse(
                $handler->toHttpResponse($e, $this->request)
            );
            
            $response->assertSeeHtml('<h1> Server Error </h1>');
            $response->assertDontSee('Sensitive Info');
            $response->assertStatus(500);
            $response->assertHeader('content-type', 'text/html');
            
        }
        
    }
    
    /**
     * DEBUG MODE
     */
    
    /** @test */
    public function detailed_output_is_provided_is_provided_for_json_errors_in_debug_mode()
    {
        
        $e = new HttpException(403, 'Sensible Info');
        $e->withMessageForUsers('Sorry, something went wrong');
        $e->withJsonMessageForUsers('Sorry, this didnt work');
        $handler = $this->newErrorHandler(true);
        $request = $this->request->withAddedHeader('Accept', 'application/json');
        
        try {
            
            throw $e;
            
        } catch (HttpException $e) {
            
            $response = new TestResponse($handler->toHttpResponse($e, $request));
            
            $response->assertIsJson()->assertForbidden();
            $response->assertExactJson([
                'message' => 'Sensible Info',
                'exception' => HttpException::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->map(function ($trace) {
                    return Arr::except($trace, ['args']);
                })->all(),
            ]);
            
        }
        
    }
    
    /** @test */
    public function in_debug_mode_exceptions_are_rendered_with_whoops_for_non_ajax_requests()
    {
        
        $e = new HttpException(403, 'Sensible Info');
        $handler = $this->newErrorHandler(true);
        
        try {
            
            throw $e;
            
        } catch (HttpException $e) {
            
            $response = new TestResponse($handler->toHttpResponse($e, $this->request));
            
            $body = $response->body();
            
            $response->assertStatus(403);
            $response->assertSee('Whoops');
            $response->assertSee(
                'Snicco\ExceptionHandling\Exceptions\HttpException: Sensible Info',
                $body
            );
            
        }
        
    }
    
    protected function beforeTearDown()
    {
        Mockery::close();
        WP::reset();
        parent::beforeTearDown();
    }
    
    protected function beforeTestRun()
    {
        $this->container = $this->createContainer();
        $this->request = TestRequest::from('GET', 'foo');
        $this->container->instance(ResponseFactory::class, $this->createResponseFactory());
        $this->container->instance(ViewFactoryInterface::class, new TestViewFactory());
    }
    
    private function newErrorHandler(bool $debug_mode = false) :ProductionExceptionHandler
    {
        
        return new ProductionExceptionHandler(
            $this->container,
            new NullLogger(),
            $this->container[ResponseFactory::class],
            $debug_mode ? $this->getWhoops() : null
        );
        
    }
    
    private function getWhoops()
    {
        
        $app = Mockery::mock(Application::class);
        $app->shouldReceive('config')->once()->with('app.editor')->andReturn('phpstorm');
        $app->shouldReceive('basePath')->twice()->withNoArgs()->andReturn(__DIR__);
        $app->shouldReceive('basePath')->once()->with('vendor')->andReturn(
            __DIR__.DIRECTORY_SEPARATOR.'vendor'
        );
        $app->shouldReceive('config')->once()->with('app.debug_blacklist', [])->andReturn([]);
        $app->shouldReceive('config')->once()->with('app.hide_debug_traces', [])->andReturn([]);
        
        return \Snicco\Support\Functions\tap(new Run(), function (Run $whoops) use ($app) {
            
            $whoops->writeToOutput(false);
            $whoops->allowQuit(false);
            $whoops->pushHandler(WhoopsHandler::get($app));
            
        });
        
    }
    
}

class RenderableException extends Exception
{
    
    public function render(ResponseFactory $factory)
    {
        return $factory->html('Foo')->withStatus(500);
    }
    
}

class ReturnsStringException extends Exception
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

