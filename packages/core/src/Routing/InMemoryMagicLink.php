<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\MagicLink;

class InMemoryMagicLink extends MagicLink
{
    
    private array $links = [];
    
    public function __construct()
    {
        $this->app_key = 'base64:LOK1UydvZ50A9iyTC2KxuP/C6k8TAM4UlGDcjwsKQik=';
    }
    
    public function notUsed(Request $request) :bool
    {
        return isset($this->links[$request->query('signature')]);
    }
    
    public function destroy($signature)
    {
        unset($this->links[$signature]);
    }
    
    public function store(string $signature, int $expires) :bool
    {
        $this->links[$signature] = $expires;
        return true;
    }
    
    public function gc() :bool
    {
        foreach ($this->links as $signature => $expires) {
            if ($expires < $this->currentTime()) {
                unset($this->links[$signature]);
            }
        }
        
        return true;
    }
    
    public function getStored() :array
    {
        return $this->links;
    }
    
}