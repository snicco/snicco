<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Throwable;
use Whoops\RunInterface;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Traits\HandlesExceptions;
use Snicco\Contracts\ExceptionHandler;
use Whoops\Handler\JsonResponseHandler;
use Snicco\Events\UnrecoverableExceptionHandled;

class DebugExceptionHandler implements ExceptionHandler
{
    
    use HandlesExceptions;
    
    private RunInterface $whoops;
    
    public function __construct(RunInterface $whoops)
    {
        
        $this->whoops = $whoops;
        
    }
    
    public function transformToResponse(Throwable $exception, Request $request) :?Response
    {
        
        $this->handleException($exception, $request);
        
        return null;
        
    }
    
    public function handleException($exception, ?Request $request = null)
    {
        
        $request = $request ?? $this->resolveRequestFromContainer();
        
        if ($request && $request->isExpectingJson()) {
            
            $json_handler = new JsonResponseHandler();
            $json_handler->addTraceToOutput(true);
            $this->whoops->prependHandler($json_handler);
            
        }
        
        $method = RunInterface::EXCEPTION_HANDLER;
        
        $this->whoops->sendHttpCode();
        
        $this->whoops->{$method}($exception);
        
        UnrecoverableExceptionHandled::dispatch();
        
    }
    
    public function unrecoverable(Throwable $exception)
    {
        $this->handleException($exception);
    }
    
}