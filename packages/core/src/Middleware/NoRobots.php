<?php

declare(strict_types=1);

namespace Snicco\Middleware;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class NoRobots extends Middleware
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
            $response = $response->noArchive();
        }
        
        if ( ! $this->index) {
            $response = $response->noIndex();
        }
        
        if ( ! $this->follow) {
            $response = $response->noFollow();
        }
        
        return $response;
    }
    
}