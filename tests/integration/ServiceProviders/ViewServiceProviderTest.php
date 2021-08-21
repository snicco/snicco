<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Tests\stubs\TestApp;
use Tests\FrameworkTestCase;
use Snicco\View\MethodField;
use Snicco\View\ViewFactory;
use Snicco\View\PhpViewEngine;
use Snicco\View\GlobalContext;
use Snicco\View\ViewComposerCollection;
use Snicco\Contracts\ViewEngineInterface;
use Snicco\Contracts\ViewFactoryInterface;

class ViewServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_global_context_is_a_singleton()
    {
        
        /** @var GlobalContext $context */
        $context = TestApp::resolve(GlobalContext::class);
        $this->assertInstanceOf(GlobalContext::class, $context);
        
        $this->assertArrayNotHasKey('foo', $context->get());
        TestApp::globals('foo', 'bar');
        
        $context = TestApp::resolve(GlobalContext::class);
        
        $this->assertArrayHasKey('foo', $context->get());
        
    }
    
    /** @test */
    public function the_view_service_is_resolved_correctly()
    {
        
        $this->assertInstanceOf(ViewFactory::class, TestApp::resolve(ViewFactoryInterface::class));
        
    }
    
    /** @test */
    public function the_view_engine_is_resolved_correctly()
    {
        
        $this->assertInstanceOf(PhpViewEngine::class, TestApp::resolve(ViewEngineInterface::class));
        
    }
    
    /** @test */
    public function the_view_composer_collection_is_resolved_correctly()
    {
        
        $this->assertInstanceOf(
            ViewComposerCollection::class,
            TestApp::resolve(ViewComposerCollection::class)
        );
        
    }
    
    /** @test */
    public function the_internal_views_are_included()
    {
        
        $views = TestApp::config('view.paths');
        
        $this->assertContains(ROOT_DIR.DS.'resources'.DS.'views', $views);
        
    }
    
    /** @test */
    public function the_method_field_can_be_resolved()
    {
        $this->assertInstanceOf(MethodField::class, TestApp::resolve(MethodField::class));
    }
    
    /** @test */
    public function the_view_factory_is_added_to_the_global_context()
    {
        $context = TestApp::globals();
        $this->assertInstanceOf(ViewFactory::class, $context->get()['__view']());
    }
    
    protected function setUp() :void
    {
        $this->bootAfterCreation();
        parent::setUp();
    }
    
}
