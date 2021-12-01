<?php

declare(strict_types=1);

namespace Snicco\ViewBundle;

use Snicco\View\ViewEngine;
use Snicco\View\GlobalViewContext;
use Snicco\Contracts\ServiceProvider;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;
use Snicco\View\Implementations\PHPViewFinder;
use Snicco\View\Contracts\ViewComposerFactory;
use Snicco\View\Implementations\PHPViewFactory;

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
    }
    
    public function bootstrap() :void
    {
        //
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
        $this->container->singleton(ViewFactory::class, PHPViewFactory::class);
    }
    
    private function bindViewEngine() :void
    {
        $this->container->singleton(ViewEngine::class, ViewEngine::class);
        $this->container->singleton(PHPViewFactory::class, function () {
            return new PHPViewFactory(
                new PHPViewFinder($this->config->get('view.paths', [])),
                $this->container->make(ViewComposerCollection::class),
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
        
        $this->container->singleton(ViewComposerCollection::class, ViewComposerCollection::class);
    }
    
}
