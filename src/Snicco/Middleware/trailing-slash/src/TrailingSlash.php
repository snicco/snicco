<?php

declare(strict_types=1);

namespace Snicco\Middleware\TrailingSlash;

use Snicco\Component\StrArr\Str;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\AbstractMiddleware;

/**
 * @api
 */
final class TrailingSlash extends AbstractMiddleware
{
    
    private bool $trailing_slash;
    
    public function __construct(bool $trailing_slash = true)
    {
        $this->trailing_slash = $trailing_slash;
    }
    
    public function handle(Request $request, NextMiddleware $next) :ResponseInterface
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