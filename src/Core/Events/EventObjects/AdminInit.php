<?php

declare(strict_types=1);

namespace Snicco\Core\Events\EventObjects;

use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use Snicco\EventDispatcher\Contracts\MappedAction;

class AdminInit extends CoreEvent implements MappedAction
{
    
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