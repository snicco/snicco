<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPAuth;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\ScopableWP\ScopableWP;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\AbstractMiddleware;

use function sprintf;

final class Authenticate extends AbstractMiddleware
{
    
    const KEY = '_user_id';
    private ScopableWP $wp;
    
    public function __construct(ScopableWP $wp = null)
    {
        $this->wp = $wp ? : new ScopableWP();
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if ($this->wp->isUserLoggedIn()) {
            return $next(
                $request->withAttribute(
                    self::KEY,
                    $this->wp->getCurrentUserId()
                )
            );
        }
        
        throw new HttpException(
            401,
            sprintf(
                'Missing authentication for request path [%s].',
                $request->path()
            )
        );
    }
    
}