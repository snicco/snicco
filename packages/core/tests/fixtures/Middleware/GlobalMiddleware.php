<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GlobalMiddleware implements MiddlewareInterface
{
    
    const run_times = 'global_middleware';
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        $count = $GLOBALS['test'][self::run_times] ?? 0;
        $count++;
        $GLOBALS['test'][self::run_times] = $count;
        
        $request->body = 'global_';
        
        return $handler->handle($request);
    }
    
}