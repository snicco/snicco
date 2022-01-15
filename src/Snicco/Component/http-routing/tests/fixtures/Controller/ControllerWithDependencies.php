<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\Controller;

use Tests\Codeception\shared\TestDependencies\Foo;
use Snicco\Component\HttpRouting\Http\Psr7\Request;

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