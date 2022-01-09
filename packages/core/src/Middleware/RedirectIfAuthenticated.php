<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Closure;
use InvalidArgumentException;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

class RedirectIfAuthenticated extends AbstractMiddleware
{
    
    private ?string $path;
    private Closure $id_provider;
    
    public function __construct(Closure $id_provider, string $path = null)
    {
        $this->id_provider = $id_provider;
        $this->path = $path;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        $id = $this->getCurrentUserId();
        
        if (0 !== $id) {
            if ($request->isExpectingJson()) {
                return $this->respond()
                            ->json(['message' => 'Only guests can access this path.'], 403);
            }
            
            if ($this->path) {
                return $this->redirect()->to($this->path);
            }
            else {
                try {
                    return $this->redirect()->toRoute('dashboard');
                } catch (RouteNotFound $e) {
                    return $this->redirect()->home();
                }
            }
        }
        
        return $next($request);
    }
    
    private function getCurrentUserId() :int
    {
        $id = call_user_func($this->id_provider);
        if ( ! is_int($id) || 0 > $id) {
            throw new InvalidArgumentException('The user id closure did not return an integer.');
        }
        return $id;
    }
    
}
