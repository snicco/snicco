<?php

declare(strict_types=1);

namespace Snicco\Middleware\Core;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Middleware;
use Snicco\Http\ResponseEmitter;
use Psr\Http\Message\ResponseInterface;

class OutputBufferMiddleware extends Middleware
{
    
    private ResponseEmitter $emitter;
    
    private ?Response       $retained_response = null;
    
    private ?Request        $request;
    
    public function __construct(ResponseEmitter $emitter)
    {
        $this->emitter = $emitter;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        
        $response = $next($request);
        
        // We delay every response that that has a body.
        // We need to keep this response in memory and only send it after WordPress
        // has loaded the admin header, navbar etc.
        // Otherwise, our content will not be inside the correct dom element in the wp-admin area.
        if ($this->shouldDelayResponse($response)) {
            
            $this->retained_response = $response;
            $this->request = $request;
            
            // Don't use a NullResponse here as we do want to send headers for redirect responses etc.
            return $this->response_factory->delegateToWP();
            
        }
        
        return $response;
        
    }
    
    private function shouldDelayResponse(Response $response) :bool
    {
        
        if (php_sapi_name() === 'cli') {
            return false;
        }
        
        if ($response->getBody()->getSize()) {
            
            return true;
            
        }
        
        return false;
        
    }
    
    public function start()
    {
        $this->cleanPhpOutputBuffer();
        ob_start();
        
    }
    
    private function cleanPhpOutputBuffer()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    
    public function flush()
    {
        
        if ($this->retained_response instanceof Response) {
            
            $this->emitter->emit($this->emitter->prepare($this->retained_response, $this->request));
            
        }
        
        // We made sure that our admin content sends headers and body at the correct time.
        // Now flush all output so the user does not have to wait until admin-footer-php finished
        // terminates the script.
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        
    }
    
}