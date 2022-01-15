<?php

declare(strict_types=1);

namespace Tests\BladeBundle\integration;

use Snicco\View\ViewEngine;
use Snicco\ViewBundle\ViewServiceProvider;
use Snicco\BladeBundle\BladeServiceProvider;
use Tests\Codeception\shared\FrameworkTestCase;

class ViewEngineWithBladeTest extends FrameworkTestCase
{
    
    /**
     * @var ViewEngine
     */
    private $view_engine;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->afterApplicationBooted(function () {
            $this->view_engine = $this->app->resolve(ViewEngine::class);
        });
    }
    
    /** @test */
    public function nested_views_can_be_rendered_relative_to_the_views_directory()
    {
        $this->withAddedConfig('view.paths', [dirname(__DIR__).'/fixtures/views']);
        $this->bootApp();
        $view = $this->view_engine->make('nested.nested-blade-view');
        
        $this->assertViewContent('FOO', $view);
    }
    
    /** @test */
    public function the_first_available_view_can_be_created()
    {
        $this->withAddedConfig('view.paths', [dirname(__DIR__).'/fixtures/views']);
        $this->bootApp();
        
        $first = $this->view_engine->make(['bogus1', 'bogus2', 'blade-view']);
        
        $this->assertViewContent('FOO', $first);
    }
    
    /** @test */
    public function a_view_can_be_rendered()
    {
        $this->withAddedConfig('view.paths', [dirname(__DIR__).'/fixtures/views']);
        $this->bootApp();
        
        $html = $this->view_engine->render(['bogus1', 'bogus2', 'blade-view']);
        
        $this->assertViewContent('FOO', $html);
    }
    
    protected function packageProviders() :array
    {
        return [
            BladeServiceProvider::class,
            ViewServiceProvider::class,
        ];
    }
    
}