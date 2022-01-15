<?php

declare(strict_types=1);

namespace Tests\HttpRouting\fixtures\Controller;

use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\AbstractController;
use Tests\Codeception\shared\TestDependencies\Baz;
use Tests\HttpRouting\fixtures\MiddlewareWithDependencies;

class ControllerWithMiddleware extends AbstractController
{
    
    const CONSTRUCTED_KEY = 'controller_with_middleware';
    private Baz $baz;
    
    public function __construct(Baz $baz)
    {
        $this->middleware(MiddlewareWithDependencies::class);
        
        $this->baz = $baz;
        
        $count = $GLOBALS['test'][self::CONSTRUCTED_KEY] ?? 0;
        $count++;
        $GLOBALS['test'][self::CONSTRUCTED_KEY] = $count;
    }
    
    public function handle(Request $request) :string
    {
        return $this->baz->baz.':controller_with_middleware';
    }
    
}

