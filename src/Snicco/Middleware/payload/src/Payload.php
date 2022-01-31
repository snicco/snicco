<?php

declare(strict_types=1);

namespace Snicco\Middleware\Payload;

use Webmozart\Assert\Assert;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\NextMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\AbstractMiddleware;

use function strpos;

/**
 * @api
 */
abstract class Payload extends AbstractMiddleware
{
    
    private array $content_types;
    /**
     * @var array<string>
     */
    private $methods;
    
    public function __construct(array $content_types, $methods = ['POST', 'PUT', 'PATCH', 'DELETE'])
    {
        Assert::allString($content_types);
        Assert::allString($methods);
        $this->content_types = $content_types;
        $this->methods = $methods;
    }
    
    final public function handle(Request $request, NextMiddleware $next) :ResponseInterface
    {
        if ( ! $this->shouldParseRequest($request)) {
            return $next($request);
        }
        
        $request = $request->withParsedBody($this->parse($request->getBody()));
        return $next($request);
    }
    
    /**
     * @throws CantParseRequestBody
     */
    abstract protected function parse(StreamInterface $stream) :array;
    
    private function shouldParseRequest(Request $request) :bool
    {
        if ( ! in_array($request->getMethod(), $this->methods, true)) {
            return false;
        }
        
        $content_type = $request->getHeaderLine('content-type');
        
        foreach ($this->content_types as $allowedType) {
            if (strpos($content_type, $allowedType) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
}