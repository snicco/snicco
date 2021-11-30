<?php

namespace Snicco\ExceptionHandling;

use Snicco\Support\Arr;
use Snicco\Support\Finder;
use Snicco\Application\Application;

class WhoopsHandler
{
    
    public static function get(Application $app)
    {
        $hide_frames = $app->config('app.hide_debug_traces', []);
        
        $handler = new FilterablePrettyPageHandler($hide_frames);
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
        
        return $handler;
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