<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Controllers\Ajax;

use Snicco\Core\Http\Psr7\Request;

class AjaxController
{
    
    public function handle(Request $request)
    {
        // This controller is never run.
        // we only assert that is can be created without a FQN.
        
    }
    
}