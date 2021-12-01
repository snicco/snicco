<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Controllers\Admin;

use Snicco\Http\Controller;
use Snicco\Http\Psr7\Request;
use Tests\Codeception\shared\TestDependencies\Baz;
use Tests\Core\fixtures\Middleware\MiddlewareWithDependencies;

class AdminControllerWithMiddleware extends Controller
{
    
    const constructed_times = 'controller_with_middleware';
    /**
     * @var Baz
     */
    private $baz;
    
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
        $request->body .= $this->baz->baz.':controller_with_middleware';
        
        return $request->body;
    }
    
}

