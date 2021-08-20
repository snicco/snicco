<?php

declare(strict_types=1);

namespace Snicco\Http;

use Psr\Http\Message\ResponseInterface;

use function header;
use function headers_sent;
use function connection_status;

/**
 * Modified Version of Slims Response Emitter.
 *
 * @link https://github.com/slimphp/Slim/blob/4.x/Slim/ResponseEmitter.php
 * Changed method visibility to accommodate WordPress needs.
 */
class ResponseEmitter
{
    
    private int $response_chunk_size;
    
    public function __construct(int $response_chunk_size = 4096)
    {
        $this->response_chunk_size = $response_chunk_size;
    }
    
    /**
     * Send the response the client
     *
     * @param  ResponseInterface  $response
     *
     * @return void
     */
    public function emit(ResponseInterface $response) :void
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
    
    /**
     * Asserts response body is empty or status code is 204, 205 or 304
     *
     * @param  ResponseInterface  $response
     *
     * @return bool
     */
    protected function isResponseEmpty(ResponseInterface $response) :bool
    {
        
        if (in_array($response->getStatusCode(), [204, 205, 304], true)) {
            return true;
        }
        $stream = $response->getBody();
        $seekable = $stream->isSeekable();
        if ($seekable) {
            $stream->rewind();
        }
        
        return $seekable ? $stream->read(1) === '' : $stream->eof();
    }
    
    /**
     * Emit Status Line
     *
     * @param  ResponseInterface  $response
     */
    protected function emitStatusLine(ResponseInterface $response) :void
    {
        
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($statusLine, true, $response->getStatusCode());
    }
    
    /**
     * Emit Response Headers
     *
     * @param  ResponseInterface  $response
     */
    public function emitHeaders(ResponseInterface $response) :void
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
    
    /**
     * Emit Body
     *
     * @param  ResponseInterface  $response
     */
    protected function emitBody(ResponseInterface $response) :void
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
    
    public function emitCookies(Cookies $cookies)
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
    
}