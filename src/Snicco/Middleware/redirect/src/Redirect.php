<?php

declare(strict_types=1);

namespace Snicco\Middleware\Redirect;

use InvalidArgumentException;
use Snicco\Component\StrArr\Arr;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

use function trim;
use function ltrim;
use function strpos;
use function is_int;
use function implode;
use function in_array;

/**
 * @api
 */
final class Redirect extends AbstractMiddleware
{
    
    /**
     * @var array<int,array>
     */
    private array $redirects;
    
    public function __construct(array $redirects)
    {
        $this->normalize($redirects);
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $path_redirect = Arr::get($this->redirects, $request->path());
        
        if ($path_redirect) {
            return $this->respond()->redirect(
                $path_redirect['to'],
                $path_redirect['status']
            );
        }
        
        $path_qs = $request->path().'?'.$request->queryString();
        $query_string_redirect = Arr::get($this->redirects, trim($path_qs));
        
        if ($query_string_redirect) {
            return $this->respond()->redirect(
                $query_string_redirect['to'],
                $query_string_redirect['status']
            );
        }
        
        return $next($request);
    }
    
    private function normalize(array $redirects) :void
    {
        $arr = [301, 302, 303, 307, 308];
        foreach ($redirects as $status => $redirect) {
            if ( ! is_int($status)) {
                throw new InvalidArgumentException(
                    "Redirects must be keyed by their HTTP status code."
                );
            }
            
            if ( ! in_array($status, $arr, true)) {
                throw new InvalidArgumentException(
                    '$status must be one of [%s].', implode(',', $arr)
                );
            }
            
            foreach ($redirect as $from => $to) {
                $from = '/'.ltrim($from, '/');
                
                $to = (0 === strpos($to, 'http'))
                    ? $to
                    : '/'.ltrim($to, '/');
                
                $this->redirects[$from] = ['to' => $to, 'status' => $status,];
            }
        }
    }
    
}