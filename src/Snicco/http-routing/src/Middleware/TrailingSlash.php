<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Middleware;

use Snicco\StrArr\Str;
use Snicco\Core\Utils\UrlPath;
use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\AbstractMiddleware;

/**
 * @note Do not use this middleware for any routes that go directly to a file on your file system.
 * @api
 */
final class TrailingSlash extends AbstractMiddleware
{
    
    private bool $trailing_slash;
    
    public function __construct(bool $trailing_slash = true)
    {
        $this->trailing_slash = $trailing_slash;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $path = UrlPath::fromString($request->path());
        
        $accept_request = $this->trailing_slash
            ? Str::endsWith($path->asString(), '/')
            : Str::doesNotEndWith($path->asString(), '/');
        
        if ($accept_request || $path->equals('/')) {
            return $next($request);
        }
        
        $redirect_to = $this->trailing_slash
            ? $path->withTrailingSlash()
            : $path->withoutTrailingSlash();
        
        return $this->redirect()->to($redirect_to->asString(), 301);
    }
    
}