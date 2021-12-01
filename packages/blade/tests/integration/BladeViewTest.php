<?php

declare(strict_types=1);

namespace Tests\Blade\integration;

use Snicco\Blade\BladeView;
use Tests\Blade\BladeTestCase;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Exceptions\ViewRenderingException;

class BladeViewTest extends BladeTestCase
{
    
    /** @test */
    public function a_blade_view_can_be_rendered()
    {
        $view = $this->view_engine->make('foo');
        
        $this->assertInstanceOf(BladeView::class, $view);
        $this->assertInstanceOf(ViewInterface::class, $view);
        $this->assertViewContent('FOO', $view->toString());
    }
    
    /** @test */
    public function variables_can_be_shared_with_a_view()
    {
        $view = $this->view_engine->make('variables');
        $view->with('name', 'calvin');
        
        $this->assertViewContent('hello calvin', $view->toString());
    }
    
    /** @test */
    public function view_errors_are_caught()
    {
        $this->expectException(ViewRenderingException::class);
        
        $view = $this->view_engine->make('variables');
        $view->with('bogus', 'calvin');
        
        $this->assertViewContent('hello calvin', $view->toString());
    }
    
    /** @test */
    public function blade_internals_are_included_in_the_view()
    {
        $view = $this->view_engine->make('internal');
        
        $this->assertViewContent('app:env', $view->toString());
    }
    
    /** @test */
    public function blade_views_can_be_rendered()
    {
        $html = $this->view_engine->render('variables', ['name' => 'calvin']);
        
        $this->assertViewContent('hello calvin', $html);
    }
    
}