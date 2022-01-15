<?php

declare(strict_types=1);

namespace Tests\ViewBundle\integration;

use Mockery;
use Snicco\Testing\TestResponse;
use Snicco\View\Contracts\ViewFactory;
use Snicco\ViewBundle\ViewServiceProvider;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\View\Exceptions\ViewRenderingException;
use Snicco\Component\Core\ExceptionHandling\ExceptionHandler;
use Snicco\Component\Core\ExceptionHandling\Exceptions\HttpException;
use Snicco\Component\Core\ExceptionHandling\ProductionExceptionHandler;
use Snicco\Component\Core\ExceptionHandling\Exceptions\ErrorViewException;

final class ViewBasedHtmlErrorRendererTest extends FrameworkTestCase
{
    
    /**
     * @var Mockery\MockInterface|ViewFactory
     */
    private $view_factory;
    
    protected function setUp() :void
    {
        $this->afterApplicationBooted(function () {
            $this->swap(
                ViewFactory::class,
                $this->view_factory = Mockery::mock(ViewFactory::class)
            );
        });
        parent::setUp();
        $this->bootApp();
    }
    
    protected function tearDown() :void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /** @test */
    public function an_exception_while_trying_to_render_a_default_error_view_will_throw_an_special_error_view_exception()
    {
        $this->view_factory->shouldReceive('make')->once()->andThrow(
            $view_exception = new ViewRenderingException()
        );
        
        /** @var ProductionExceptionHandler $handler */
        $handler = $this->app->resolve(ExceptionHandler::class);
        
        try {
            $response = new TestResponse(
                $handler->toHttpResponse(
                    new HttpException(500, 'Sensitive Info'),
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
            $response->assertHeader('content-type', 'text/html; charset=UTF-8');
        }
    }
    
    protected function packageProviders() :array
    {
        return [ViewServiceProvider::class];
    }
    
}