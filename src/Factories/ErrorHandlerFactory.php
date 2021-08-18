<?php

declare(strict_types=1);

namespace Snicco\Factories;

use Whoops\Run;
use Whoops\RunInterface;
use Psr\Log\LoggerInterface;
use Snicco\Http\Psr7\Request;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Whoops\Handler\PrettyPageHandler;
use Snicco\ExceptionHandling\DebugErrorHandler;
use Snicco\ExceptionHandling\ProductionErrorHandler;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class ErrorHandlerFactory
{
    
    const ALLOWED_EDITORS = [
        'emacs',
        'idea',
        'macvim',
        'phpstorm',
        'sublime',
        'textmate',
        'xdebug',
        'vscode',
        'atom',
        'espresso',
    ];
    
    /**
     * @throws ConfigurationException
     */
    public static function make(ContainerAdapter $container, bool $is_debug, string $editor = null)
    {
        
        if ( ! $is_debug || ! class_exists(Run::class)) {
            
            $production_handler = static::createProductionHandler($container);
            $production_handler->setRequestResolver(function () use ($container) {
                
                return $container->make(Request::class);
            });
            
            return $production_handler;
            
        }
        
        [$whoops, $pretty_page_handler] = static::createWhoops($container);
        
        if ($editor) {
            
            static::setEditor($pretty_page_handler, $editor);
            
        }
        
        $debug_handler = new DebugErrorHandler($whoops);
        
        $debug_handler->setRequestResolver(function () use ($container) {
            
            return $container->make(Request::class);
        });
        
        return $debug_handler;
        
    }
    
    private static function createProductionHandler(ContainerAdapter $container) :ProductionErrorHandler
    {
        
        $logger = $container->make(LoggerInterface::class);
        
        $response_factory = $container->make(ResponseFactory::class);
        
        $class = $container->make(ProductionErrorHandler::class);
        
        return new $class($container, $logger, $response_factory);
        
    }
    
    private static function createWhoops(ContainerAdapter $container) :array
    {
        
        $whoops = new Run();
        $pretty_page_handler = new PrettyPageHandler();
        $pretty_page_handler->handleUnconditionally(true);
        
        $whoops->appendHandler($pretty_page_handler);
        $whoops->allowQuit(false);
        $whoops->writeToOutput(true);
        
        $container->instance(RunInterface::class, $whoops);
        $container->instance(PrettyPageHandler::class, $pretty_page_handler);
        
        return [$whoops, $pretty_page_handler];
        
    }
    
    private static function setEditor(PrettyPageHandler $handler, string $editor)
    {
        
        if ( ! in_array($editor, static::ALLOWED_EDITORS)) {
            
            throw new ConfigurationException(
                'The editor: '.$editor.' is not supported by Whoops.'
            );
            
        }
        
        $handler->setEditor($editor);
        
    }
    
}