<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;

class NoRobots extends AbstractMiddleware
{
    
    private bool $archive;
    private bool $follow;
    private bool $index;
    
    public function __construct($noindex = 'noindex', $nofollow = 'nofollow', $noarchive = 'noarchive')
    {
        $this->index = strtolower($noindex) !== 'noindex';
        $this->follow = strtolower($nofollow) !== 'nofollow';
        $this->archive = strtolower($noarchive) !== 'noarchive';
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        /** @var Response $response */
        $response = $next($request);
        
        if ( ! $this->archive) {
            $response = $response->withNoArchive();
        }
        
        if ( ! $this->index) {
            $response = $response->withNoIndex();
        }
        
        if ( ! $this->follow) {
            $response = $response->withNoFollow();
        }
        
        return $response;
    }
    
}