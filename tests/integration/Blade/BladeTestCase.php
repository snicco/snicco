<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Tests\FrameworkTestCase;
use Snicco\Blade\BladeServiceProvider;
use Snicco\Blade\BladeDirectiveServiceProvider;

class BladeTestCase extends FrameworkTestCase
{
    
    protected static $ignore_files = [];
    
    protected function packageProviders() :array
    {
        
        return [
            BladeServiceProvider::class,
            BladeDirectiveServiceProvider::class,
        ];
    }
    
    protected function setUp() :void
    {
        
        parent::setUp();
        $this->rmdir(BLADE_CACHE);
    }
    
}