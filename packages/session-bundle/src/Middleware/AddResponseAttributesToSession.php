<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Middleware;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Delegate;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\AbstractMiddleware;

use function Snicco\SessionBundle\getWriteSession;

/**
 * @interal
 */
final class AddResponseAttributesToSession extends AbstractMiddleware
{
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        $session = getWriteSession($request);
        foreach ($response->errors() as $namespace => $errors) {
            $session->withErrors($errors, $namespace);
        }
        
        foreach ($response->flashMessages() as $key => $message) {
            $session->flash($key, $message);
        }
        
        $old = $response->oldInput();
        
        if ( ! empty($old)) {
            $session->flashInput($old);
        }
        
        return $response;
    }
    
}