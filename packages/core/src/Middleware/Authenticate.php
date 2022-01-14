<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Closure;
use InvalidArgumentException;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\AbstractMiddleware;

/**
 * @api
 */
final class Authenticate extends AbstractMiddleware
{
    
    private Closure $current_user_id;
    
    public function __construct(Closure $current_user_id)
    {
        $this->current_user_id = $current_user_id;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if ($this->getCurrentUserId() > 0) {
            return $next($request);
        }
        
        if ($request->isExpectingJson()) {
            return $this->respond()
                        ->json('Authentication Required', 401);
        }
        
        $login = $this->url()->toLogin();
        return $this->redirect()->deny($login);
    }
    
    private function getCurrentUserId() :int
    {
        $id = call_user_func($this->current_user_id);
        if ( ! is_int($id) || 0 > $id) {
            throw new InvalidArgumentException('The user id closure did not return an integer.');
        }
        return $id;
    }
    
}
