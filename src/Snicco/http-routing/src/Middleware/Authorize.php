<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\AbstractMiddleware;
use Snicco\Component\Core\ExceptionHandling\Exceptions\AuthorizationException;

/**
 * @api
 */
final class Authorize extends AbstractMiddleware
{
    
    private string  $capability;
    private ?int    $object_id;
    private Closure $grant_access;
    
    public function __construct(Closure $grant_access, $capability, int $object_id = null)
    {
        $this->grant_access = $grant_access;
        $this->capability = $capability;
        $this->object_id = $object_id;
    }
    
    /**
     * @throws AuthorizationException
     */
    public function handle(Request $request, $next) :ResponseInterface
    {
        $args = [];
        if ($this->object_id) {
            $args[] = $this->object_id;
        }
        
        if ($this->userCan($args)) {
            return $next($request);
        }
        
        throw new AuthorizationException(
            "Authorization failed for path [{$request->path()}] with required capability [$this->capability]."
        );
    }
    
    private function userCan(array $args) :bool
    {
        return call_user_func($this->grant_access, $this->capability, $args);
    }
    
}
