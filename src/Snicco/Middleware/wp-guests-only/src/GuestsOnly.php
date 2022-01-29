<?php

declare(strict_types=1);

namespace Snicco\Middleware\GuestsOnly;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\ScopableWP\ScopableWP;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;

/**
 * @api
 */
final class GuestsOnly extends AbstractMiddleware
{
    
    private ScopableWP $wp;
    private ?string    $redirect_to;
    private string     $json_message;
    
    public function __construct(ScopableWP $wp, string $redirect_to = null, string $json_message = 'You are already authenticated')
    {
        $this->wp = $wp;
        $this->redirect_to = $redirect_to;
        $this->json_message = $json_message;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if (false === $this->wp->isUserLoggedIn()) {
            return $next($request);
        }
        
        if ($request->isExpectingJson()) {
            return $this->respond()
                        ->json(['message' => $this->json_message], 403);
        }
        
        if ($this->redirect_to) {
            return $this->redirect()->to($this->redirect_to);
        }
        else {
            try {
                return $this->redirect()->toRoute('dashboard');
            } catch (RouteNotFound $e) {
                return $this->redirect()->home();
            }
        }
    }
    
}
