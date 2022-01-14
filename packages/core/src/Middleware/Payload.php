<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use RuntimeException;
use Snicco\Support\Str;
use Webmozart\Assert\Assert;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\AbstractMiddleware;
use Snicco\Core\ExceptionHandling\Exceptions\HttpException;

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
        
        try {
            $request = $request->withParsedBody($this->parse($request->getBody()));
            
            return $next($request);
        } catch (RuntimeException $exception) {
            throw new HttpException(
                500, $exception->getMessage()
            );
        }
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