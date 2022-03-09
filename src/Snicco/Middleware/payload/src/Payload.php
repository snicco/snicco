<?php

declare(strict_types=1);

namespace Snicco\Middleware\Payload;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Webmozart\Assert\Assert;

use function strpos;

abstract class Payload extends Middleware
{
    /**
     * @var string[]
     */
    private array $content_types;

    /**
     * @var string[]
     */
    private array $methods;

    /**
     * @param string[] $content_types
     * @param string[] $methods
     */
    public function __construct(array $content_types, array $methods = ['POST', 'PUT', 'PATCH', 'DELETE'])
    {
        Assert::allString($content_types);
        Assert::allString($methods);
        $this->content_types = $content_types;
        $this->methods = $methods;
    }

    final public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        if (! $this->shouldParseRequest($request)) {
            return $next($request);
        }

        $request = $request->withParsedBody($this->parse($request->getBody()));

        return $next($request);
    }

    /**
     * @throws CantParseRequestBody
     *
     * @return array<string,mixed>
     */
    abstract protected function parse(StreamInterface $stream): array;

    private function shouldParseRequest(Request $request): bool
    {
        if (! in_array($request->getMethod(), $this->methods, true)) {
            return false;
        }

        $content_type = $request->getHeaderLine('content-type');

        foreach ($this->content_types as $allowedType) {
            if (0 === strpos($content_type, $allowedType)) {
                return true;
            }
        }

        return false;
    }
}
