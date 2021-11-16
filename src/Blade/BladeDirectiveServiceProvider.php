<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Snicco\Support\WP;
use Snicco\Application\Application;
use Snicco\Contracts\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Snicco\Application\ApplicationTrait;

class BladeDirectiveServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        //
    }
    
    function bootstrap() :void
    {
        Blade::if('auth', fn() => WP::isUserLoggedIn());
        
        Blade::if('guest', fn() => ! WP::isUserLoggedIn());
        
        Blade::if('role', function ($expression) {
            if ($expression === 'admin') {
                $expression = 'administrator';
            }
            
            return WP::userIs($expression);
        });
        
        Blade::directive('service', function ($expression) {
            $segments = explode(',', preg_replace("/[()]/", '', $expression));
            
            $variable = trim($segments[0], " '\"");
            
            $service = trim($segments[1]);
            
            $app = $this->container->make(ApplicationTrait::class);
            
            $php = "<?php \${$variable} = {$app}::resolve({$service}::class) ?>";
            
            return $php;
        });
        
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