<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Closure;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

use function sprintf;

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
        
        throw new HttpException(
            401,
            sprintf(
                "Missing authentication for request path [%s].",
                $request->path()
            )
        );
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
