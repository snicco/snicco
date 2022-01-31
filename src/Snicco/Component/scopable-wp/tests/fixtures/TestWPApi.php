<?php

declare(strict_types=1);

namespace Snicco\Component\ScopableWP\Tests\fixtures;

use Snicco\Component\ScopableWP\ScopableWP;

class TestWPApi extends ScopableWP
{
    
    public function method1() :string
    {
        return 'method1';
    }
    
}