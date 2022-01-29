<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures\TestDependencies;

final class Foo
{
    
    public string $value;
    
    public function __construct($value = 'foo')
    {
        $this->value = $value;
    }
    
}