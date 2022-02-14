<?php

declare(strict_types=1);

namespace Snicco\Middleware\NoRobots;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;

/**
 * @api
 */
final class NoRobots extends Middleware
{

    private bool $noarchive;
    private bool $nofollow;
    private bool $noindex;

    public function __construct(bool $noindex = true, bool $nofollow = true, bool $noarchive = true)
    {
        $this->noindex = $noindex;
        $this->nofollow = $nofollow;
        $this->noarchive = $noarchive;
    }

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        if ($this->noarchive) {
            $response = $response->withNoArchive();
        }

        if ($this->noindex) {
            $response = $response->withNoIndex();
        }

        if ($this->nofollow) {
            $response = $response->withNoFollow();
        }

        return $response;
    }

}