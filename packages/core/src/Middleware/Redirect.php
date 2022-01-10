<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Support\Arr;
use Snicco\Support\Str;
use Snicco\Core\Support\Url;
use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;

class Redirect extends AbstractMiddleware
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
        $location = Url::addLeading($location);
        
        if ($with_trailing) {
            $location = Url::addTrailing($location);
        }
        
        return $location;
    }
    
}