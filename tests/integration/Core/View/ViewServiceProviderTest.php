<?php

declare(strict_types=1);

namespace Tests\integration\Core\View;

use Snicco\View\ViewEngine;
use Tests\FrameworkTestCase;
use Snicco\View\GlobalViewContext;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;
use Snicco\View\Implementations\PHPViewFactory;

use const DS;
use const ROOT_DIR;

class ViewServiceProviderTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bootApp();
    }
    
    /** @test */
    public function the_global_context_is_a_singleton()
    {
        /** @var GlobalViewContext $context */
        $context = $this->app->resolve(GlobalViewContext::class);
        $this->assertInstanceOf(GlobalViewContext::class, $context);
        
        $this->assertArrayNotHasKey('foo', $context->get());
        $context->add('foo', 'bar');
        
        $context_new = $this->app->resolve(GlobalViewContext::class);
        
        $this->assertArrayHasKey('foo', $context_new->get());
    }
    
    /** @test */
    public function the_view_engine_is_resolved_correctly()
    {
        $this->assertInstanceOf(ViewEngine::class, $this->app->resolve(ViewEngine::class));
    }
    
    /** @test */
    public function the_view_factory_is_resolved_correctly()
    {
        $this->assertInstanceOf(
            PHPViewFactory::class,
            $this->app->resolve(ViewFactory::class)
        );
    }
    
    /** @test */
    public function the_view_composer_collection_is_resolved_correctly()
    {
        $this->assertInstanceOf(
            ViewComposerCollection::class,
            $this->app->resolve(ViewComposerCollection::class)
        );
    }
    
    /** @test */
    public function the_internal_views_are_included()
    {
        $views = $this->app->config('view.paths');
        
        $this->assertSame(ROOT_DIR.DS.'resources'.DS.'views', end($views));
    }
    
}
