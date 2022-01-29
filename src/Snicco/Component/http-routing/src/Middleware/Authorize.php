<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

use function sprintf;

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
     * @throws HttpException
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
        
        throw new HttpException(
            403,
            sprintf(
                "Authorization failed for path [%s] with required capability [%s].",
                $request->path(),
                $this->capability
            )
        );
    }
    
    private function userCan(array $args) :bool
    {
        return call_user_func($this->grant_access, $this->capability, $args);
    }
    
}
