<?php

declare(strict_types=1);

namespace Snicco\Core\ExceptionHandling;

use Psr\Log\NullLogger;
use Whoops\Run as Whoops;
use Whoops\RunInterface;
use Psr\Log\LoggerInterface;
use Snicco\Core\Http\Delegate;
use Illuminate\Container\Container;
use Whoops\Handler\HandlerInterface;
use Snicco\Core\Http\MiddlewarePipeline;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Contracts\ExceptionHandler;
use Snicco\Core\Contracts\AbstractMiddleware;

class ExceptionServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindErrorHandler();
        $this->bindPsr3Logger();
        $this->bindWhoops();
        $this->bindHtmlRenderer();
    }
    
    public function bootstrap() :void
    {
        //
    }
    
    private function bindConfig() :void
    {
        $this->config->extendIfEmpty(
            'app.hide_debug_traces',
            fn() => [
                MiddlewarePipeline::class,
                Container::class,
                Delegate::class,
                AbstractMiddleware::class,
            ]
        );
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
                $this->container->get(LoggerInterface::class),
                $this->container->get(ResponseFactory::class),
                $with_whoops ? $this->container[RunInterface::class] : null
            );
        });
    }
    
    private function bindPsr3Logger()
    {
        $this->container->singleton(LoggerInterface::class, function () {
            $filter_frames = $this->config->get('app.hide_debug_traces', []);
            
            return $this->app->isRunningUnitTest()
                ? new NullLogger()
                : new NativeErrorLogger($filter_frames);
        });
    }
    
    private function bindWhoops()
    {
        if ($this->config->get('app.debug') === true && class_exists(Whoops::class)) {
            $this->container->singleton(RunInterface::class, function () {
                $whoops = new Whoops();
                $whoops->appendHandler($this->whoopsHandler());
                $whoops->writeToOutput(false);
                $whoops->allowQuit(false);
                return $whoops;
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
    
    private function bindHtmlRenderer()
    {
        $this->container->singleton(HtmlErrorRender::class, function () {
            return new PlainTextHtmlErrorRenderer();
        });
    }
    
}
