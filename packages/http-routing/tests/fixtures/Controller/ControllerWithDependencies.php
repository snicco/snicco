<?php

declare(strict_types=1);

namespace Tests\HttpRouting\fixtures\Controller;

use Snicco\HttpRouting\Http\Psr7\Request;
use Tests\Codeception\shared\TestDependencies\Foo;

class ControllerWithDependencies
{
    
    private Foo $foo;
    
    public function __construct(Foo $foo)
    {
        $this->foo = $foo;
    }
    
    public function __invoke(Request $request) :string
    {
        return $this->foo->foo.'_controller';
    }
    
}