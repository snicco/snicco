<?php

namespace Snicco\Component\Core\ExceptionHandling;

use Snicco\Component\StrArr\Arr;
use Snicco\Component\Core\Utils\DirectoryFinder;
use Snicco\Component\Core\Application\Application_OLD;

class WhoopsHandler
{
    
    public static function get(Application_OLD $app)
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
    
    public static function allDirsExpectVendor(Application_OLD $app) :array
    {
        $dirs = Arr::except(
            array_flip((new DirectoryFinder())->allDirsInDir($app->basePath())),
            [$app->basePath('vendor')]
        );
        
        return array_flip($dirs);
    }
    
}