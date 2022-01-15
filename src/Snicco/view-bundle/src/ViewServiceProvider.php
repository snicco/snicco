<?php

declare(strict_types=1);

namespace Snicco\ViewBundle;

use Snicco\View\ViewEngine;
use InvalidArgumentException;
use Snicco\View\GlobalViewContext;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\HttpRouting\Http\TemplateRenderer;
use Snicco\View\Implementations\PHPViewFinder;
use Snicco\View\Contracts\ViewComposerFactory;
use Snicco\View\Implementations\PHPViewFactory;
use Snicco\Core\ExceptionHandling\HtmlErrorRender;

use function in_array;
use function strtoupper;

class ViewServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $ds = DIRECTORY_SEPARATOR;
        $this->extendViews(
            $this->config->get('app.package_root').$ds.'resources'.$ds.'views'.$ds.'framework'
        );
        
        $this->bindGlobalContext();
        
        $this->bindViewFactoryInterface();
        
        $this->bindViewEngine();
        
        $this->bindViewComposerCollection();
        
        $this->bindTemplateRenderer();
        
        $this->bindHtmlErrorRenderer();
        
        $this->bindViewComposerFactory();
    }
    
    public function bootstrap() :void
    {
        $this->shareMethodField();
    }
    
    private function bindViewComposerFactory() :void
    {
        $this->container->singleton(DependencyInjectionViewComposerFactory::class, function () {
            return new DependencyInjectionViewComposerFactory(
                $this->container,
                $this->config['view.composers'] ?? []
            );
        });
    }
    
    private function bindGlobalContext()
    {
        // This has to be a singleton.
        $this->container->singleton(GlobalViewContext::class, function () {
            return new GlobalViewContext();
        });
    }
    
    private function bindViewFactoryInterface() :void
    {
        $this->container->singleton(ViewFactory::class, function () {
            return $this->container[PHPViewFactory::class];
        });
    }
    
    private function bindViewEngine() :void
    {
        $this->container->singleton(ViewEngine::class, function () {
            return new ViewEngine($this->container[ViewFactory::class]);
        });
        
        $this->container->singleton(PHPViewFactory::class, function () {
            return new PHPViewFactory(
                new PHPViewFinder($this->config->get('view.paths', [])),
                $this->container->get(ViewComposerCollection::class),
            );
        });
    }
    
    private function bindViewComposerCollection() :void
    {
        $this->container->singleton(ViewComposerFactory::class, function () {
            return new DependencyInjectionViewComposerFactory(
                $this->container,
                $this->config['view.composers'] ?? []
            );
        });
        
        $this->container->singleton(ViewComposerCollection::class, function () {
            return new ViewComposerCollection(
                $this->container[DependencyInjectionViewComposerFactory::class],
                $this->container[GlobalViewContext::class]
            );
        });
    }
    
    private function bindTemplateRenderer()
    {
        $this->container->singleton(TemplateRenderer::class, function () {
            return new ViewEngineTemplateRenderer($this->container[ViewEngine::class]);
        });
    }
    
    private function bindHtmlErrorRenderer()
    {
        $this->container->singleton(HtmlErrorRender::class, function () {
            return new ViewBasedHtmlErrorRenderer(
                $this->container[ViewEngine::class]
            );
        });
    }
    
    private function shareMethodField()
    {
        /** @var GlobalViewContext $global_context */
        $global_context = $this->container[GlobalViewContext::class];
        
        $method_field = new class
        {
            
            public function __invoke(string $method) :string
            {
                $method = strtoupper($method);
                
                if ( ! in_array($method, $arr = ['PATCH', 'PUT', 'DELETE'], true)) {
                    throw new InvalidArgumentException(
                        sprintf("$method has to be one of [%s]", implode(',', $arr))
                    );
                }
                return "<input type='hidden' name='_method' value='{$method}'>";
            }
            
        };
        
        $global_context->add('method', $method_field);
    }
    
}

