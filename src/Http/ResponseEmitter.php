<?php

declare(strict_types=1);

namespace Snicco\Http;

use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;

use function header;
use function headers_sent;
use function connection_status;

/**
 * Modified Version of Slims Response Emitter.
 *
 * @link https://github.com/slimphp/Slim/blob/4.x/Slim/ResponseEmitter.php
 */
class ResponseEmitter
{
    
    protected ResponsePreparation $preparation;
    
    private int $response_chunk_size;
    
    public function __construct(ResponsePreparation $preparation, int $response_chunk_size = 4096)
    {
        $this->response_chunk_size = $response_chunk_size;
        $this->preparation = $preparation;
    }
    
    public function emit(Response $response) :void
    {
        $isEmpty = $this->isResponseEmpty($response);
        
        if ($headers_not_sent = headers_sent() === false) {
            $this->emitStatusLine($response);
            $this->emitHeaders($response);
        }
        
        if ( ! $isEmpty && $headers_not_sent) {
            $this->emitBody($response);
        }
    }
    
    public function emitHeaders(Response $response) :void
    {
        if (headers_sent()) {
            return;
        }
        
        foreach ($response->getHeaders() as $name => $values) {
            $replace = strtolower($name) !== 'set-cookie';
            foreach ($values as $value) {
                $header = sprintf('%s: %s', $name, $value);
                header($header, $replace);
                $replace = false;
            }
        }
    }
    
    public function prepare(Response $response, Request $request) :Response
    {
        return $this->preparation->prepare($response, $request);
    }
    
    public function emitCookies(Cookies $cookies) :void
    {
        if (headers_sent()) {
            return;
        }
        
        $cookies = $cookies->toHeaders();
        
        foreach ($cookies as $cookie) {
            $header = sprintf('%s: %s', 'Set-Cookie', $cookie);
            header($header, false);
        }
    }
    
    protected function isResponseEmpty(Response $response) :bool
    {
        if ($response->isEmpty()) {
            return true;
        }
        $stream = $response->getBody();
        $seekable = $stream->isSeekable();
        if ($seekable) {
            $stream->rewind();
        }
        
        return $seekable ? $stream->read(1) === '' : $stream->eof();
    }
    
    protected function emitStatusLine(Response $response) :void
    {
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true, $response->getStatusCode());
    }
    
    protected function emitBody(Response $response) :void
    {
        $body = $response->getBody();
        
        if ($body->isSeekable()) {
            $body->rewind();
        }
        
        $amountToRead = (int) $response->getHeaderLine('Content-Length');
        if ( ! $amountToRead) {
            $amountToRead = $body->getSize();
        }
        
        if ($amountToRead) {
            while ($amountToRead > 0 && ! $body->eof()) {
                $length = min($this->response_chunk_size, $amountToRead);
                $data = $body->read($length);
                echo $data;
                
                $amountToRead -= strlen($data);
                
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        }
        else {
            while ( ! $body->eof()) {
                echo $body->read($this->response_chunk_size);
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }
    
}