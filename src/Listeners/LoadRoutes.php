<?php

declare(strict_types=1);

namespace Snicco\Listeners;

use Snicco\Support\Str;
use Snicco\Events\WpInit;
use Snicco\Http\Psr7\Request;
use Snicco\Application\Config;
use Snicco\Events\IncomingApiRequest;
use Snicco\Contracts\RouteRegistrarInterface;

class LoadRoutes
{
    
    public function __invoke(WpInit $event, RouteRegistrarInterface $registrar)
    {
        
        $config = $event->config;
        $registrar->loadApiRoutes($config);
        $registrar->loadStandardRoutes($config);
        $registrar->loadIntoRouter();
        
        if ($this->isApiEndpoint($event->request, $event->config)) {
            
            // This will run as the first hook on init.
            IncomingApiRequest::dispatch([$event->request]);
            
        }
        
    }
    
    private function isApiEndpoint(Request $request, Config $config)
    {
        
        $endpoints = $config->get('routing.api.endpoints', []);
        
        foreach ($endpoints as $endpoint) {
            
            if (Str::startsWith(trim($request->path(), '/'), trim($endpoint, '/'))) {
                
                return true;
                
            }
            
        }
        
        return false;
        
    }
    
}