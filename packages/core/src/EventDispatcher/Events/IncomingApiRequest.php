<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Events;

use Snicco\Support\Str;
use Snicco\Http\Psr7\Request;
use Snicco\Application\Config;

class IncomingApiRequest extends IncomingRequest
{
    
    private Config $config;
    
    public function __construct(Request $request, Config $config)
    {
        parent::__construct($request);
        $this->config = $config;
    }
    
    public function shouldDispatch() :bool
    {
        return $this->isApiEndpoint();
    }
    
    private function isApiEndpoint() :bool
    {
        $endpoints = $this->config->get('routing.api.endpoints', []);
        
        foreach ($endpoints as $endpoint) {
            if (Str::startsWith(trim($this->request->path(), '/'), trim($endpoint, '/'))) {
                return true;
            }
        }
        
        return false;
    }
    
}