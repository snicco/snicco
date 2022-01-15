<?php

declare(strict_types=1);

namespace Snicco\Core\EventDispatcher\Events;

use Snicco\StrArr\Str;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\Core\Configuration\WritableConfig;

class IncomingApiRequest extends IncomingRequest
{
    
    private WritableConfig $config;
    
    public function __construct(Request $request, WritableConfig $config)
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