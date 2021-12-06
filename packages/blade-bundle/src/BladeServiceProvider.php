<?php

declare(strict_types=1);

namespace Snicco\BladeBundle;

use RuntimeException;
use Snicco\Blade\BladeStandalone;
use Snicco\Contracts\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;
use Snicco\Application\ApplicationTrait;
use Snicco\ViewBundle\ViewServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $foo = 'bar';
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
        if ($this->sessionEnabled()) {
            Blade::directive('csrf', function () {
                $app = $this->container->get(ApplicationTrait::class);
                return "<?php echo {$app}::csrfField() ?>";
            });
        }
        
        Blade::directive('method', function ($method) {
            $method = str_replace("'", '', $method);
            $app = $this->container->get(ApplicationTrait::class);
            return "<?php echo {$app}::methodField('$method') ?>";
        });
    }
    
}