<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use BetterWpHooks\Traits\IsAction;
use BetterWpHooks\Traits\DispatchesConditionally;

class AdminInit extends Event
{
    
    use IsAction;
    use DispatchesConditionally;
    
    public Request $request;
    public ?string $hook;
    
    public function __construct(Request $request)
    {
        
        $this->request = $request;
        
        $this->hook = WP::pluginPageHook();
        
        if ( ! $this->hook) {
            
            global $pagenow;
            $this->hook = $pagenow;
            
        }
        
    }
    
    public function shouldDispatch() :bool
    {
        return $this->request->isWpAdmin();
    }
    
}