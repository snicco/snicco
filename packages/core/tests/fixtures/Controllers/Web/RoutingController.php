<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Controllers\Web;

use Snicco\Http\Psr7\Request;

class RoutingController
{
    
    public function foo(Request $request)
    {
        return 'foo';
    }
    
}