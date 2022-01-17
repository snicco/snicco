<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Middleware;

use Snicco\View\GlobalViewContext;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

use function Snicco\SessionBundle\getReadSession;

/**
 * @interal
 */
final class ShareSessionWithViews extends AbstractMiddleware
{
    
    /**
     * @var GlobalViewContext
     */
    private $view_context;
    
    public function __construct(GlobalViewContext $view_context)
    {
        $this->view_context = $view_context;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ( ! $request->isGet()) {
            return $next($request);
        }
        
        $read_session = getReadSession($request);
        
        $this->view_context->add('session', $read_session);
        $this->view_context->add('errors', $read_session->errors());
        $this->view_context->add('csrf', $read_session->csrfToken());
        
        return $next($request);
    }
    
}