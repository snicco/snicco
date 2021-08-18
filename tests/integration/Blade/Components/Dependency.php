<?php

declare(strict_types=1);

namespace Tests\integration\Blade\Components;

use Snicco\Blade\BladeComponent;
use Tests\fixtures\TestDependencies\Foo;

class Dependency extends BladeComponent
{
    
    public Foo    $foo;
    public string $message;
    
    protected $except = ['foo'];
    
    public function __construct(Foo $foo, $message)
    {
        $this->foo = $foo;
        $this->message = $foo->foo.$message;
    }
    
    public function render()
    {
        
        return $this->view('components.with-dependency');
        
    }
    
}