<?php

namespace Snicco\Core\Http;

use Snicco\StrArr\Str;
use Snicco\StrArr\Arr;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function strval;
use function ini_set;
use function strtolower;
use function str_replace;
use function headers_list;
use function ob_get_length;

/*
 *
 * This class represents Symfonys Response::prepare() method ported to psr7.
 *
 * @link https://github.com/symfony/http-foundation/blob/5.3/ResponseHeaderBag.php
 * @link https://github.com/symfony/http-foundation/blob/5.3/Response.php
 *
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * @license https://github.com/symfony/http-foundation/blob/5.3/LICENSE
 */

final class ResponsePreparation
{
    
    private StreamFactoryInterface $stream_factory;
    private string                 $charset;
    
    public function __construct(StreamFactoryInterface $stream_factory, string $charset = 'UTF-8')
    {
        $this->stream_factory = $stream_factory;
        $this->charset = $charset;
    }
    
    public function prepare(Psr7\Response $response, Psr7\Request $request) :Psr7\Response
    {
        $response = $this->fixDate($response);
        $response = $this->fixCacheControl($response);
        $response = $this->fixContent($response, $request);
        return $this->fixProtocol($response, $request);
    }
    
    private function fixDate(Response $response)
    {
        if ( ! $response->hasHeader('date')) {
            $response = $response->withHeader('date', gmdate('D, d M Y H:i:s').' GMT');
        }
        
        return $response;
    }
    
    // There is no need to remove a header possibly added by a call to header() in WordPress
    // since our ResponseEmitter will take of this anyway.
    private function fixCacheControl(Response $response) :Response
    {
        $header = $this->getCacheControlHeader($response);
        
        if ($header === '') {
            if ($response->hasHeader('Last-Modified') || $response->hasHeader('Expires')) {
                // allows for heuristic expiration (RFC 7234 Section 4.2.2) in the case of "Last-Modified"
                return $response->withHeader('Cache-Control', 'private, must-revalidate');
            }
            
            // conservative by default
            return $response->withHeader('Cache-Control', 'no-cache, private');
        }
        
        if (Str::contains($header, ['public', 'private'])) {
            return $response;
        }
        
        // public if s-maxage is defined, private otherwise
        if ( ! Str::contains($header, 's-maxage')) {
            return $response->withHeader('Cache-Control', $header.', private');
        }
        
        return $response;
    }
    
    private function getCacheControlHeader(Response $response)
    {
        if ($response->hasHeader('cache-control')) {
            return strtolower($response->getHeaderLine('cache-control'));
        }
        
        $header = Arr::first(headers_list(), function ($header) {
            return Str::startsWith(strtolower($header), 'cache-control');
        }, '');
        
        return str_replace('cache-control: ', '', strtolower($header));
    }
    
    private function fixContent(Response $response, Request $request) :Response
    {
        if ($response->isInformational() || $response->isEmpty()) {
            // prevent PHP from sending the Content-Type header based on default_mimetype
            ini_set('default_mimetype', '');
            
            return $response->withBody($this->getEmptyStream())
                            ->withoutHeader('content-type')
                            ->withoutHeader('content-length');
        }
        
        // Fix content type
        $content_type = $response->getHeaderLine('content-type');
        
        if ($content_type === '') {
            $response = $response->withContentType("text/html; charset=$this->charset");
        }
        elseif (Str::startsWith($content_type, 'text/')
                && ! Str::contains($content_type, 'charset')) {
            $content_type = trim($content_type, ';');
            $response = $response->withContentType("$content_type; charset=$this->charset");
        }
        
        // Fix Content-Length, don't add if anything is buffered since we will mess up plugins that use it.
        if ( ! $response->hasHeader('content-length')
             && ! $response->hasEmptyBody()
             && ! ob_get_length()
        ) {
            $size = strval($response->getBody()->getSize());
            $response = $response->withHeader('content-length', $size);
        }
        
        // Remove content-length if transfer-encoding
        if ($response->hasHeader('transfer-encoding')) {
            $response = $response->withoutHeader('content-length');
        }
        
        // Fix HEAD method if body: RFC2616 14.13
        if ($request->isHead()) {
            $response = $response->withBody($this->getEmptyStream());
        }
        
        return $response;
    }
    
    private function fixProtocol(Response $response, Request $request) :Response
    {
        if ('HTTP/1.0' != $request->server('SERVER_PROTOCOL')) {
            $response = $response->withProtocolVersion('1.1');
        }
        
        return $response;
    }
    
    private function getEmptyStream() :StreamInterface
    {
        return $this->stream_factory->createStream('');
    }
    
}