<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Controllers\Admin;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\AbstractController;
use Tests\Codeception\shared\TestDependencies\Baz;
use Tests\Core\fixtures\Middleware\MiddlewareWithDependencies;

class ControllerWithMiddleware extends AbstractController
{
    
    const constructed_times = 'controller_with_middleware';
    private Baz $baz;
    
    public function __construct(Baz $baz)
    {
        $this->middleware(MiddlewareWithDependencies::class);
        
        $this->baz = $baz;
        
        $count = $GLOBALS['test'][self::constructed_times] ?? 0;
        $count++;
        $GLOBALS['test'][self::constructed_times] = $count;
    }
    
    public function handle(Request $request) :string
    {
        return $this->baz->baz.':controller_with_middleware';
    }
    
}

