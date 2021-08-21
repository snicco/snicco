<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Snicco\Contracts\ServiceProvider;
use Snicco\Factories\ErrorHandlerFactory;
use Snicco\Contracts\ErrorHandlerInterface;

class ExceptionServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindErrorHandlerInterface();
        $this->bindPsr3Logger();
    }
    
    public function bootstrap() :void
    {
        //$error_handler = $this->container->make(ErrorHandlerInterface::class);
        //
        //$error_handler->register();
        //
        //$this->container->instance(ErrorHandlerInterface::class, $error_handler);
    }
    
    private function bindConfig() :void
    {
        
        $this->config->extend('view.paths', [__DIR__.DIRECTORY_SEPARATOR.'views']);
        
        // We bind the class name only
        $this->container->instance(ProductionErrorHandler::class, ProductionErrorHandler::class);
        
    }
    
    private function bindErrorHandlerInterface() :void
    {
        $this->container->singleton(ErrorHandlerInterface::class, function () {
            
            if ( ! $this->config->get('app.exception_handling', false)) {
                return new NullErrorHandler();
            }
            
            $debug = $this->config->get('app.debug') && ! $this->app->isRunningUnitTest();
            
            return ErrorHandlerFactory::make(
                $this->container,
                $debug,
                $this->config->get('app.debug_editor', 'phpstorm')
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
    
}
