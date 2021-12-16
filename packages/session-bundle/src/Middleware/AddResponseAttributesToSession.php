<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Middleware;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

use function Snicco\SessionBundle\getWriteSession;

/**
 * @interal
 */
final class AddResponseAttributesToSession extends Middleware
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