<?php

declare(strict_types=1);

namespace Snicco\Middleware\Core;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Middleware;
use Snicco\Http\ResponseEmitter;
use Psr\Http\Message\ResponseInterface;
use Snicco\Http\Responses\NullResponse;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Http\Responses\DelegatedResponse;

use function add_action;

class OutputBufferMiddleware extends Middleware
{
    
    private ResponseEmitter        $emitter;
    private ?Response              $retained_response = null;
    private ?Request               $request;
    private int                    $initial_level;
    private StreamFactoryInterface $stream_factory;
    
    public function __construct(ResponseEmitter $emitter, StreamFactoryInterface $stream_factory)
    {
        $this->emitter = $emitter;
        $this->stream_factory = $stream_factory;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        // We delay every response that that has a body.
        // We need to keep this response in memory and only send it after WordPress
        // has loaded the admin header, navbar etc.
        // Otherwise, our content will not be inside the correct dom element in the wp-admin area.
        if ($this->shouldDelayResponse($response)) {
            $this->startOutputBuffer();
            add_action('all_admin_notices', [$this, 'flush']);
            $this->retained_response = $response;
            $this->request = $request;
            
            // We don't want to send any headers or output yet.
            return $this->response_factory->null();
        }
        
        return $response;
    }
    
    public function startOutputBuffer()
    {
        $this->initial_level = ob_get_level();
        ob_start();
    }
    
    public function flush()
    {
        if ($this->retained_response instanceof Response) {
            $body = $this->stream_factory->createStream();
            $body->write($this->getBufferedOutput().$this->retained_response->getBody());
            $response = $this->retained_response->withBody($body);
            
            $this->emitter->emit($this->emitter->prepare($response, $this->request));
        }
    }
    
    /**
     * @note ob_get_clean will close the most recent buffer first and return the content.
     * This is not what we want. We want the first buffered content to come first and everything
     * else appended.
     */
    private function getBufferedOutput() :string
    {
        $output = '';
        while (ob_get_level() > $this->initial_level) {
            $output = ob_get_clean().$output;
        }
        return $output;
    }
    
    private function shouldDelayResponse(Response $response) :bool
    {
        if ($response instanceof NullResponse || $response instanceof DelegatedResponse) {
            return false;
        }
        
        if ( ! $response->isSuccessful()) {
            return false;
        }
        
        if ($response->getBody()->getSize()) {
            return true;
        }
        
        return false;
    }
    
}