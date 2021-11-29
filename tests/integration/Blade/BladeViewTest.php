<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Tests\stubs\TestApp;
use Snicco\View\ViewEngine;
use Snicco\Http\ResponseFactory;
use Snicco\View\Exceptions\ViewRenderingException;

class BladeViewTest extends BladeTestCase
{
    
    private ViewEngine $engine;
    
    protected function setUp() :void
    {
        $this->afterApplicationBooted(function () {
            $this->engine = $this->app->resolve(ViewEngine::class);
        });
        
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function a_blade_view_can_be_rendered()
    {
        $view = $this->engine->make('foo');
        
        $this->assertViewContent('FOO', $view->toString());
    }
    
    /** @test */
    public function a_blade_view_can_be_transformed()
    {
        $view = $this->engine->make('foo');
        
        /** @var ResponseFactory $response_factory */
        $response_factory = $this->app->resolve(ResponseFactory::class);
        
        $this->assertSame('FOO', $response_factory->toResponse($view)->getBody()->__toString());
    }
    
    /** @test */
    public function variables_can_be_shared_with_a_view()
    {
        $view = $this->engine->make('variables');
        $view->with('name', 'calvin');
        
        $this->assertViewContent('hello calvin', $view->toString());
    }
    
    /** @test */
    public function view_errors_are_caught()
    {
        $this->expectException(ViewRenderingException::class);
        
        $view = $this->engine->make('variables');
        $view->with('bogus', 'calvin');
        
        $this->assertViewContent('hello calvin', $view->toString());
    }
    
    /** @test */
    public function blade_internals_are_included_in_the_view()
    {
        $view = $this->engine->make('internal');
        
        $this->assertViewContent('app:env', $view->toString());
    }
    
    /** @test */
    public function the_view_service_can_render_the_blade_view()
    {
        ob_start();
        
        TestApp::render('variables', ['name' => 'calvin']);
        
        $html = ob_get_clean();
        
        $this->assertViewContent('hello calvin', $html);
    }
    
}