<?php

declare(strict_types=1);

namespace Snicco\Core\Blade;

use Snicco\Blade\BladeStandalone;
use Snicco\Application\Application;
use Snicco\Contracts\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;
use Snicco\Application\ApplicationTrait;

class BladeServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $blade = $this->registerBlade();
        
        $this->createFrameworkViewDirectives();
        
        $this->container->singleton(ViewFactory::class, function () use ($blade) {
            return $blade->getBladeViewFactory();
        });
    }
    
    function bootstrap() :void
    {
        //
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
            $this->container->make(ViewComposerCollection::class)
        )))->boostrap();
        return $blade;
    }
    
    private function createFrameworkViewDirectives() :void
    {
        Blade::directive('csrf', function () {
            /** @var Application $app */
            $app = $this->container->make(ApplicationTrait::class);
            
            $php = "<?php echo {$app}::csrfField() ?>";
            
            return $php;
        });
        Blade::directive('method', function ($method) {
            $method = str_replace("'", '', $method);
            
            /** @var Application $app */
            $app = $this->container->make(ApplicationTrait::class);
            
            $php = "<?php echo {$app}::methodField('$method') ?>";
            
            return $php;
        });
    }
    
}