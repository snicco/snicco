<?php

namespace Snicco\ExceptionHandling;

use Snicco\Support\Finder;
use Illuminate\Support\Arr;
use Snicco\Application\Application;
use Whoops\Handler\PrettyPageHandler;

use function Snicco\Support\Functions\tap as tap;

class WhoopsHandler
{
    
    public static function get(Application $app)
    {
        
        $hide_frames = $app->config('app.hide_debug_traces', []);
        
        return tap(
            new FilterablePrettyPageHandler($hide_frames),
            function (PrettyPageHandler $handler) use ($app) {
                
                $handler->handleUnconditionally(true);
                
                if ($editor = $app->config('app.editor')) {
                    
                    $handler->setEditor($editor);
                    $handler->setApplicationRootPath($app->basePath());
                    $handler->setApplicationPaths(self::allDirsExpectVendor($app));
                    
                }
                
                foreach ($app->config('app.debug_blacklist', []) as $key => $secrets) {
                    
                    foreach ($secrets as $secret) {
                        
                        $handler->blacklist($key, $secret);
                        
                    }
                    
                }
            }
        );
    }
    
    public static function allDirsExpectVendor(Application $app) :array
    {
        
        $dirs = Arr::except(
            array_flip((new Finder())->directories($app->basePath())),
            [$app->basePath('vendor')]
        );
        
        return array_flip($dirs);
        
    }
    
}