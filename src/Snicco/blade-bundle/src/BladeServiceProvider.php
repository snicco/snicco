<?php

declare(strict_types=1);

namespace Snicco\BladeBundle;

use LogicException;
use RuntimeException;
use Snicco\Blade\BladeStandalone;
use Illuminate\Support\Facades\Blade;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;
use Snicco\ViewBundle\ViewServiceProvider;
use Snicco\Component\Core\Contracts\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
    }
    
    function bootstrap() :void
    {
        if ( ! class_exists(ViewServiceProvider::class)) {
            throw new RuntimeException(
                "sniccowp/blade-bundle needs sniccowp/view-bundle. Did you forget to add the ViewServiceProvider?"
            );
        }
        
        $blade = $this->registerBlade();
        
        $this->container->singleton(ViewFactory::class, function () use ($blade) {
            return $blade->getBladeViewFactory();
        });
        $this->createFrameworkViewDirectives();
    }
    
    private function registerBlade() :BladeStandalone
    {
        $cache_dir = $this->config->get(
            'view.blade_cache',
            $this->app->storagePath('framework'.DIRECTORY_SEPARATOR.'views')
        );
        
        ($blade = (new BladeStandalone(
            $cache_dir,
            $this->config['view.paths'],
            $this->container->get(ViewComposerCollection::class)
        )))->boostrap();
        return $blade;
    }
    
    private function createFrameworkViewDirectives() :void
    {
        Blade::directive('csrf', function () {
            throw new LogicException(
                'The csrf directive does not work. You should use the $csrf object that all views have access to if you are using the stateful middleware group.'
            );
        });
    
        Blade::directive('method', function () {
            throw new LogicException(
                'The method directive does not work. You should use the $method object that all views have access to.'
            );
        });
    }
    
}