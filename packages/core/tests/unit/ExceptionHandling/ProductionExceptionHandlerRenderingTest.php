<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling;

use Mockery;
use Exception;
use Whoops\Run;
use RuntimeException;
use Psr\Log\NullLogger;
use Snicco\Support\Arr;
use Snicco\Core\Support\WP;
use Snicco\Core\DIContainer;
use Snicco\Testing\TestResponse;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Http\ResponseFactory;
use Tests\Codeception\shared\UnitTest;
use Snicco\Core\Application\Application_OLD;
use Snicco\Core\Http\DefaultResponseFactory;
use Snicco\Testing\Concerns\CreatePsrRequests;
use Snicco\Core\ExceptionHandling\WhoopsHandler;
use Snicco\Core\Routing\UrlGenerator\UrlGenerator;
use Snicco\Core\ExceptionHandling\HtmlErrorRender;
use Tests\Codeception\shared\helpers\CreateContainer;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\Core\ExceptionHandling\Exceptions\HttpException;
use Snicco\Core\ExceptionHandling\ProductionExceptionHandler;
use Snicco\Core\ExceptionHandling\PlainTextHtmlErrorRenderer;

class ProductionExceptionHandlerRenderingTest extends UnitTest
{
    
    use CreateContainer;
    use CreatePsr17Factories;
    use CreatePsrRequests;
    
    private DIContainer $container;
    
    private Request $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->container = $this->createContainer();
        $this->request = $this->frontendRequest('GET', '/foo');
        $this->container->instance(
            ResponseFactory::class,
            $this->createResponseFactory(
                Mockery::mock(UrlGenerator::class)
            )
        );
        $this->container->instance(
            HtmlErrorRender::class,
            new PlainTextHtmlErrorRenderer()
        );
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        Mockery::close();
        WP::reset();
    }
    
    /** @test */
    public function exceptions_are_transformed_to_response_objects()
    {
        $handler = $this->newErrorHandler();
        
        $response = new TestResponse(
            $handler->toHttpResponse(new fixtures\TestException('Sensitive Info'), $this->request)
        );
        
        $this->assertInstanceOf(Response::class, $response->psr_response);
        $response->assertSeeHtml(
            '<h1>Something went wrong.</h1>'
        );
        $response->assertDontSee('Sensitive Info');
        $response->assertStatus(500);
        $response->assertContentType('text/html');
    }
    
    /** @test */
    public function the_response_will_be_sent_as_json_if_the_request_expects_json()
    {
        $handler = $this->newErrorHandler();
        $request = $this->request->withAddedHeader('Accept', 'application/json');
        
        $response = new TestResponse(
            $handler->toHttpResponse(new fixtures\TestException('Sensitive Info'), $request)
        );
        
        $response->assertIsJson();
        $response->assertStatus(500);
        $response->assertExactJson(['message' => 'Something went wrong.']);
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
            '<h1>Something went wrong.</h1>'
        );
        $response->assertDontSee('Sensitive Info');
        $response->assertStatus(500);
        $response->assertContentType('text/html');
    }
    
    /** @test */
    public function an_exception_can_have_custom_rendering_logic()
    {
        $handler = $this->newErrorHandler();
        
        $response = new TestResponse(
            $handler->toHttpResponse(
                new fixtures\RenderableException('Sensitive Info'),
                $this->request
            )
        );
        
        $this->assertInstanceOf(Response::class, $response->psr_response);
        $response->assertSee('Foo');
        $response->assertDontSee('Sensitive Info');
        $response->assertStatus(500);
        $response->assertContentType('text/html');
    }
    
    /** @test */
    public function renderable_exceptions_must_return_response_object()
    {
        $handler = $this->newErrorHandler();
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                "Return value of %s::render() has to be an instance of [%s]",
                fixtures\ReturnsStringException::class,
                Response::class
            )
        );
        
        $handler->toHttpResponse(
            new fixtures\ReturnsStringException('Sensitive Info'),
            $this->request
        );
    }
    
    /** @test */
    public function renderable_exceptions_receive_the_current_request_and_a_response_factory_instance()
    {
        $handler = $this->newErrorHandler();
        
        $response = new TestResponse(
            $handler->toHttpResponse(
                new fixtures\ExceptionWithDependencyInjection(),
                $this->request->withAttribute('foo', 'bar')
            )
        );
        
        $response->assertStatus(403);
        $response->assertContentType('text/html');
        $response->assertSee('bar');
    }
    
    /** @test */
    public function a_custom_error_handler_can_replace_the_default_response_object()
    {
        $handler = new fixtures\TestExceptionHandler(
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
            '<h1>Custom Error Message</h1>'
        );
    }
    
    /** @test */
    public function exception_rendering_can_be_overwritten_for_every_exception()
    {
        $handler = $this->newErrorHandler();
        
        $handler->renderable(
            function (fixtures\RenderableException $e, Request $request, DefaultResponseFactory $response_factory) {
                return $response_factory->html('FOO_CUSTOMIZED')->withStatus(403);
            }
        );
        
        // No custom renderer for general Exceptions
        $response =
            new TestResponse($handler->toHttpResponse(new Exception('Error'), $this->request));
        $response->assertStatus(500);
        $response->assertSeeHtml(
            '<h1>Something went wrong.</h1>'
        );
        
        $response = new TestResponse(
            $handler->toHttpResponse(new fixtures\RenderableException('Error'), $this->request)
        );
        $response->assertStatus(403);
        $response->assertSeeHtml('FOO_CUSTOMIZED');
    }
    
    /** @test */
    public function custom_messages_for_users_are_displayed_instead_of_debug_messages()
    {
        $e = new HttpException(403, 'Sensible Info');
        $e->withMessageForUsers('Sorry, something went wrong');
        $e->withJsonMessageForUsers('Sorry, this didnt work');
        
        $handler = $this->newErrorHandler();
        
        $response = new TestResponse(
            $handler->toHttpResponse($e, $this->request)
        );
        
        $response->assertForbidden()->assertContentType('text/html');
        $response->assertSee('Sorry, something went wrong');
        
        $request = $this->request->withAddedHeader('Accept', 'application/json');
        
        $response = new TestResponse(
            $handler->toHttpResponse($e, $request)
        );
        
        $response->assertIsJson()->assertForbidden();
        $response->assertExactJson(['message' => 'Sorry, this didnt work']);
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
                'trace' => array_map(function ($trace) {
                    return Arr::except($trace, ['args']);
                }, $e->getTrace()),
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
        $app = Mockery::mock(Application_OLD::class);
        $app->shouldReceive('config')->once()->with('app.editor')->andReturn('phpstorm');
        $app->shouldReceive('basePath')->twice()->withNoArgs()->andReturn(__DIR__);
        $app->shouldReceive('basePath')->once()->with('vendor')->andReturn(
            __DIR__.DIRECTORY_SEPARATOR.'vendor'
        );
        $app->shouldReceive('config')->once()->with('app.debug_blacklist', [])->andReturn([]);
        $app->shouldReceive('config')->once()->with('app.hide_debug_traces', [])->andReturn([]);
        
        $whoops = new Run();
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->pushHandler(WhoopsHandler::get($app));
        return $whoops;
    }
    
}

