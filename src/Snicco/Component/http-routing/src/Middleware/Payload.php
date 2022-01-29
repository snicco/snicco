<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use RuntimeException;
use Webmozart\Assert\Assert;
use Snicco\Component\StrArr\Str;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

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
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ( ! $this->shouldParseRequest($request)) {
            return $next($request);
        }
        
        $request = $request->withParsedBody($this->parse($request->getBody()));
        return $next($request);
    }
    
    /**
     * @param  StreamInterface  $stream
     *
     * @return array
     * @throws RuntimeException
     */
    abstract protected function parse(StreamInterface $stream) :array;
    
    private function shouldParseRequest(Request $request) :bool
    {
        if ( ! in_array($request->getMethod(), $this->methods, true)) {
            return false;
        }
        
        return Str::contains($request->getHeaderLine('Content-Type'), $this->content_types);
    }
    
}