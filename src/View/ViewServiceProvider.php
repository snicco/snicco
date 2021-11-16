<?php

declare(strict_types=1);

namespace Snicco\View;

use Snicco\Contracts\ServiceProvider;
use Snicco\Contracts\ViewEngineInterface;
use Snicco\Factories\ViewComposerFactory;
use Snicco\Contracts\ViewFactoryInterface;

class ViewServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $ds = DIRECTORY_SEPARATOR;
        $this->extendViews($this->config->get('app.package_root').$ds.'resources'.$ds.'views');
        
        $this->bindMethodField();
        
        $this->bindGlobalContext();
        
        $this->bindViewFactoryInterface();
        
        $this->bindPhpViewEngine();
        
        $this->bindViewEngineInterface();
        
        $this->bindViewComposerCollection();
    }
    
    public function bootstrap() :void
    {
        $context = $this->container->make(GlobalContext::class);
        $context->add('__view', fn() => $this->container->make(ViewFactoryInterface::class));
    }
    
    private function bindMethodField()
    {
        $this->container->singleton(MethodField::class, fn() => new MethodField($this->appKey()));
    }
    
    private function bindGlobalContext()
    {
        // This has to be a singleton.
        $this->container->instance(GlobalContext::class, new GlobalContext());
    }
    
    private function bindViewFactoryInterface() :void
    {
        $this->container->singleton(ViewFactoryInterface::class, ViewFactory::class);
    }
    
    private function bindPhpViewEngine() :void
    {
        $this->container->singleton(PhpViewEngine::class, function () {
            return new PhpViewEngine(
                new PhpViewFinder($this->config->get('view.paths', []))
            );
        });
    }
    
    private function bindViewEngineInterface() :void
    {
        $this->container->singleton(ViewEngineInterface::class, PhpViewEngine::class);
    }
    
    private function bindViewComposerCollection() :void
    {
        $this->container->singleton(ViewComposerCollection::class, function () {
            return new ViewComposerCollection(
                $this->container->make(ViewComposerFactory::class),
            );
        });
    }
    
}
