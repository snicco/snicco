<?php

declare(strict_types=1);

namespace Tests\BladeBundle\integration;

use Snicco\BladeBundle\BladeServiceProvider;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;

class AppAliasTest extends FrameworkTestCase
{
    
    /** @test */
    public function nested_views_can_be_rendered_relative_to_the_views_directory()
    {
        $this->withAddedConfig('view.paths', [dirname(__DIR__).'/fixtures/views']);
        $this->bootApp();
        $view = TestApp::view('nested.nested-blade-view');
        
        $this->assertViewContent('FOO', $view);
    }
    
    /** @test */
    public function the_first_available_view_can_be_created()
    {
        $this->withAddedConfig('view.paths', [dirname(__DIR__).'/fixtures/views']);
        $this->bootApp();
        
        $first = TestApp::view(['bogus1', 'bogus2', 'blade-view']);
        
        $this->assertViewContent('FOO', $first);
    }
    
    /** @test */
    public function a_view_can_be_rendered()
    {
        $this->withAddedConfig('view.paths', [dirname(__DIR__).'/fixtures/views']);
        $this->bootApp();
        
        ob_start();
        TestApp::render(['bogus1', 'bogus2', 'blade-view']);
        $html = ob_get_clean();
        
        $this->assertViewContent('FOO', $html);
    }
    
    protected function packageProviders() :array
    {
        return [
            BladeServiceProvider::class,
        ];
    }
    
}