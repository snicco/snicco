<?php

declare(strict_types=1);

namespace Snicco\Http;

use LogicException;
use Snicco\Support\Arr;

class ControllerMiddleware
{
    
    private string $middleware;
    
    /**
     * Methods the middleware applies to.
     *
     * @var string[]
     */
    private array $whitelist = [];
    
    /**
     * Methods the middleware does not apply to.
     *
     * @var string[]
     */
    private array $blacklist = [];
    
    public function __construct(string $middleware)
    {
        $this->middleware = $middleware;
    }
    
    /**
     * Set methods the middleware should apply to.
     *
     * @param  string|string[]  $methods
     *
     * @throws LogicException
     */
    public function only($methods) :ControllerMiddleware
    {
        if ( ! empty($this->blacklist)) {
            throw new LogicException(
                'The only() method cant be combined with the except() method for one middleware'
            );
        }
        
        $this->whitelist = Arr::wrap($methods);
        
        return $this;
    }
    
    /**
     * Set methods the middleware should not apply to.
     *
     * @param  string|string[]  $methods
     *
     * @throws LogicException
     */
    public function except($methods) :ControllerMiddleware
    {
        if ( ! empty($this->whitelist)) {
            throw new LogicException(
                'The only() method cant be combined with the except() method for one middleware'
            );
        }
        
        $this->blacklist = Arr::wrap($methods);
        
        return $this;
    }
    
    public function appliesTo(string $method = null) :bool
    {
        if (in_array($method, $this->blacklist, true)) {
            return false;
        }
        
        if (empty($this->whitelist)) {
            return true;
        }
        
        return in_array($method, $this->whitelist, true);
    }
    
    public function name()
    {
        return $this->middleware;
    }
    
}