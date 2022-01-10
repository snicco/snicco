<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\Middleware;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Session\ValueObjects\CsrfToken;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\SessionBundle\Exceptions\InvalidCsrfTokenException;

use function sprintf;
use function is_string;
use function hash_equals;
use function Snicco\SessionBundle\getReadSession;

/**
 * @interal
 */
final class VerifyCsrfToken extends AbstractMiddleware
{
    
    /**
     * The URIs that should be excluded from CSRF verification.
     * Can a full url or a path. /* can be used to indicate a WildCard.
     *
     * @var string[]
     */
    private $except;
    
    public function __construct(array $except = [])
    {
        $this->except = $except;
    }
    
    /**
     * @throws InvalidCsrfTokenException
     */
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ( ! $this->checkCsrfToken($request)) {
            return $next($request);
        }
        
        if ( ! $this->tokensMatch($request)) {
            throw new InvalidCsrfTokenException(
                sprintf(
                    'Failed CSRF check for request path [%s].',
                    $request->path()
                )
            );
        }
        
        return $next($request);
    }
    
    private function checkCsrfToken(Request $request) :bool
    {
        if ($request->isMethodSafe()) {
            return false;
        }
        
        foreach ($this->except as $except) {
            if ($request->fullUrlIs($except) || $request->pathIs($except)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function tokensMatch(Request $request) :bool
    {
        $token = $request->post(CsrfToken::INPUT_KEY)
            ? : $request->getHeaderLine('X-CSRF-TOKEN', '');
        
        $read_session = getReadSession($request);
        
        if ( ! is_string($token)) {
            return false;
        }
        
        return hash_equals($read_session->csrfToken()->asString(), $token);
    }
    
}