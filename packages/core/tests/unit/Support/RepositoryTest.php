<?php

declare(strict_types=1);

namespace Tests\Core\unit\Support;

use Snicco\Support\Repository;
use Tests\Codeception\shared\UnitTest;

class RepositoryTest extends UnitTest
{
    
    /** @test */
    public function single_elements_can_be_added()
    {
        $var_bag = new Repository();
        
        $var_bag->add(['foo' => 'bar']);
        
        $this->assertSame('bar', $var_bag['foo']);
    }
    
    /** @test */
    public function multiple_elements_can_be_added()
    {
        $var_bag = new Repository();
        
        $var_bag->add(['foo' => 'bar', 'bar' => 'baz']);
        
        $this->assertSame('bar', $var_bag['foo']);
        $this->assertSame('baz', $var_bag['bar']);
    }
    
    /** @test */
    public function nested_elements_are_accessible_as_dot_notation()
    {
        $var_bag = new Repository();
        
        $var_bag->add([
            
            'foo' => [
                
                'bar' => [
                    
                    'baz' => 'biz',
                
                ],
            
            ],
        
        ]);
        
        $this->assertSame('biz', $var_bag['foo.bar.baz']);
    }
    
}
