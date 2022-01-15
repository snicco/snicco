<?php

declare(strict_types=1);

namespace Tests\ViewBundle\integration;

use Snicco\View\ViewEngine;
use Snicco\View\GlobalViewContext;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;
use Snicco\ViewBundle\ViewServiceProvider;
use Snicco\HttpRouting\Http\TemplateRenderer;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\View\Implementations\PHPViewFactory;
use Snicco\ViewBundle\ViewEngineTemplateRenderer;
use Snicco\ViewBundle\ViewBasedHtmlErrorRenderer;
use Snicco\Core\ExceptionHandling\HtmlErrorRender;
use Tests\View\fixtures\ViewComposers\FooComposer;
use Tests\Codeception\shared\TestDependencies\Bar;
use Snicco\ViewBundle\DependencyInjectionViewComposerFactory;

use const DS;

class ViewServiceProviderTest extends FrameworkTestCase
{
    
    /** @test */
    public function the_global_context_is_a_singleton()
    {
        $this->bootApp();
        
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
        $this->bootApp();
        
        $this->assertInstanceOf(ViewEngine::class, $this->app->resolve(ViewEngine::class));
    }
    
    /** @test */
    public function the_view_factory_is_resolved_correctly()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            PHPViewFactory::class,
            $this->app->resolve(ViewFactory::class)
        );
    }
    
    /** @test */
    public function the_view_composer_collection_is_resolved_correctly()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            ViewComposerCollection::class,
            $composers = $this->app->resolve(ViewComposerCollection::class)
        );
        
        $this->assertSame($composers, $this->app->resolve(ViewComposerCollection::class));
    }
    
    /** @test */
    public function the_view_composer_factory_is_bound()
    {
        $this->bootApp();
        $this->assertInstanceOf(
            DependencyInjectionViewComposerFactory::class,
            TestApp::resolve(DependencyInjectionViewComposerFactory::class)
        );
    }
    
    /** @test */
    public function the_view_composer_namespace_can_be_configured_correctly()
    {
        $this->bootApp();
        $this->app->container()->singleton(FooComposer::class, function () {
            return new FooComposer(new Bar());
        });
        
        /** @var DependencyInjectionViewComposerFactory $factory */
        $factory = TestApp::resolve(DependencyInjectionViewComposerFactory::class);
        
        $composer = $factory->create('FooComposer');
        
        $this->assertInstanceOf(FooComposer::class, $composer);
    }
    
    /** @test */
    public function custom_view_directories_can_be_provided()
    {
        $this->withAddedConfig('view.paths', [__DIR__]);
        $this->bootApp();
        $views = $this->app->config('view.paths');
        
        $this->assertSame(__DIR__, $views[0]);
    }
    
    /** @test */
    public function the_internal_views_are_still_included_but_with_a_lower_priority()
    {
        $this->withAddedConfig('view.paths', [__DIR__]);
        $this->bootApp();
        $views = $this->app->config('view.paths');
        
        $this->assertSame(
            $this->app->config('app.package_root').DS.'resources'.DS.'views'.DS.'framework',
            end($views)
        );
    }
    
    /** @test */
    public function the_template_renderer_is_bound()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            ViewEngineTemplateRenderer::class,
            $f2 = $this->app->resolve(TemplateRenderer::class)
        );
    }
    
    /** @test */
    public function the_html_error_renderer_is_bound()
    {
        $this->bootApp();
        $this->assertInstanceOf(
            ViewBasedHtmlErrorRenderer::class,
            $this->app->resolve(HtmlErrorRender::class)
        );
    }
    
    protected function packageProviders() :array
    {
        return [ViewServiceProvider::class];
    }
    
}
