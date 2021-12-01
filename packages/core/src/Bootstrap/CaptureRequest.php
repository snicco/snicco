<?php

namespace Snicco\Bootstrap;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Bootstrapper;
use Snicco\Application\Application;
use Psr\Http\Message\UriFactoryInterface;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

class CaptureRequest implements Bootstrapper
{
    
    public function bootstrap(Application $app) :void
    {
        if (isset($app[Request::class]) && $app->isRunningUnitTest()) {
            return;
        }
        
        $app[ServerRequestCreator::class] = $creator = $this->serverRequestCreator($app);
        $app[Request::class] = new Request($creator->fromGlobals());
    }
    
    private function serverRequestCreator(Application $app) :ServerRequestCreator
    {
        return new ServerRequestCreator(
            $app->resolve(ServerRequestFactoryInterface::class),
            $app->resolve(UriFactoryInterface::class),
            $app->resolve(UploadedFileFactoryInterface::class),
            $app->resolve(StreamFactoryInterface::class)
        );
    }
    
}