<?php

declare(strict_types=1);

namespace Snicco\Factories;

use Closure;
use Exception;
use Snicco\Contracts\Handler;
use Illuminate\Support\Reflector;
use Snicco\Contracts\RouteAction;
use Snicco\Routing\ClosureAction;
use Snicco\Http\MiddlewareResolver;
use Snicco\Routing\ControllerAction;

class RouteActionFactory extends AbstractFactory
{
    
    public function create($raw_handler, $routespace)
    {
        
        if ($this->isClosure($raw_handler)) {
            
            return $this->createUsing($raw_handler);
            
        }
        
        if ( ! Reflector::isCallable($raw_handler) && ! empty($routespace)) {
            
            return $this->createUsing(
                $routespace.'\\'.$raw_handler
            );
            
        }
        
        return $this->createUsing($raw_handler);
        
    }
    
    /**
     * @param  string|array|callable  $raw_handler
     *
     * @return RouteAction
     * @throws Exception
     */
    public function createUsing($raw_handler) :Handler
    {
        
        $handler = $this->normalizeInput($raw_handler);
        
        if ($handler[0] instanceof Closure) {
            
            return new ClosureAction($handler[0], $this->wrapClosure($handler[0]));
            
        }
        
        if ($namespaced_handler = $this->checkIsCallable($handler)) {
            
            return new ControllerAction(
                $namespaced_handler,
                new MiddlewareResolver($this->container),
                $this->container
            );
            
        }
        
        $this->fail($handler[0], $handler[1]);
        
    }
    
}