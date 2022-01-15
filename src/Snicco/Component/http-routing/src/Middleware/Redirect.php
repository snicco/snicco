<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Snicco\Component\StrArr\Str;
use Snicco\Component\StrArr\Arr;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\Core\Utils\UrlPath;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

/**
 * @api
 */
final class Redirect extends AbstractMiddleware
{
    
    /**
     * @var array
     */
    private $redirects;
    
    public function __construct(array $redirects = [], string $cache_file = null)
    {
        if ( ! $cache_file) {
            $this->redirects = $this->normalize($redirects);
            
            return;
        }
        
        if ( ! is_file($cache_file)) {
            $this->redirects = $this->normalize($redirects);
            file_put_contents($cache_file, json_encode($this->redirects));
            
            return;
        }
        
        $this->redirects = json_decode(file_get_contents($cache_file), true);
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $path = $request->fullRequestTarget();
        
        if ($redirect = Arr::get($this->redirects, trim($path, '/'))) {
            $location = $this->formatLocation($redirect['to'], $path);
            
            return $this->redirect()->to($location, $redirect['status']);
        }
        
        return $next($request);
    }
    
    private function normalize(array $redirects) :array
    {
        $r = [];
        
        foreach ($redirects as $status => $redirect) {
            foreach ($redirect as $from => $to) {
                $r[trim($from, '/')] = [
                    'to' => trim($to, '/'),
                    'status' => $status,
                ];
            }
        }
        
        return $r;
    }
    
    private function formatLocation(string $location, string $request_path) :string
    {
        $with_trailing = Str::endsWith($request_path, '/');
        $location = UrlPath::fromString($location);
        
        if ($with_trailing) {
            $location = $location->withTrailingSlash();
        }
        
        return $location->asString();
    }
    
}