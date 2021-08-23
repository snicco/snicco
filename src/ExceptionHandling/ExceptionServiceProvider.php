<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Psr\Log\NullLogger;
use Whoops\Run as Whoops;
use Whoops\RunInterface;
use Psr\Log\LoggerInterface;
use Snicco\Http\ResponseFactory;
use Whoops\Handler\HandlerInterface;
use Snicco\Contracts\ServiceProvider;
use Snicco\Contracts\ExceptionHandler;

use function Snicco\Support\Functions\tap;

class ExceptionServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindErrorHandler();
        $this->bindPsr3Logger();
        $this->bindWhoops();
    }
    
    public function bootstrap() :void
    {
        //
    }
    
    private function bindConfig() :void
    {
        $this->config->extend('view.paths', [__DIR__.DIRECTORY_SEPARATOR.'views']);
    }
    
    private function bindErrorHandler() :void
    {
        $this->container->singleton(ExceptionHandler::class, function () {
            
            if ( ! $this->config->get('app.exception_handling', false)) {
                return new NullExceptionHandler();
            }
            
            $with_whoops = ! $this->app->isProduction()
                           && isset($this->container[RunInterface::class]);
            
            return new ProductionExceptionHandler(
                $this->container,
                $this->container->make(LoggerInterface::class),
                $this->container->make(ResponseFactory::class),
                $with_whoops ? $this->container[RunInterface::class] : null
            );
            
        });
    }
    
    private function bindPsr3Logger()
    {
        $this->container->singleton(LoggerInterface::class, function () {
            
            return $this->app->isRunningUnitTest()
                ? new NullLogger()
                : new NativeErrorLogger();
            
        });
    }
    
    private function bindWhoops()
    {
        
        if ($this->config->get('app.debug') === true && class_exists(Whoops::class)) {
            
            $this->container->singleton(RunInterface::class, function () {
                
                return tap(new Whoops(), function (Whoops $whoops) {
                    
                    $whoops->appendHandler($this->whoopsHandler());
                    $whoops->writeToOutput(false);
                    $whoops->allowQuit(false);
                    
                });
                
            });
        }
        
    }
    
    private function whoopsHandler()
    {
        if (isset($this->container[HandlerInterface::class])) {
            return $this->container[HandlerInterface::class];
        }
        
        return $this->app[HandlerInterface::class] = WhoopsHandler::get($this->app);
    }
    
}
