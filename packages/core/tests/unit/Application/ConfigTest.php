<?php

declare(strict_types=1);

namespace Tests\Core\unit\Application;

use Snicco\Application\Config;
use Tests\Codeception\shared\UnitTest;

class ConfigTest extends UnitTest
{
    
    private Config $config;
    
    public function setUp() :void
    {
        parent::setUp();
        
        $this->config = new Config();
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        
        unset($this->config);
    }
    
    /** @test */
    public function a_value_can_be_a_boolean_false()
    {
        $this->config->extend('foo', false);
        
        $this->assertSame(false, $this->config->get('foo'));
    }
    
    /** @test */
    public function a_value_can_be_null()
    {
        $this->config->extend('foo', null);
        
        $this->assertSame(null, $this->config->get('foo'));
    }
    
    /** @test */
    public function a_value_can_be_string_int_zero()
    {
        $this->config->extend('foo', '0');
        $this->config->extend('bar', 0);
        
        $this->assertSame('0', $this->config->get('foo'));
        $this->assertSame(0, $this->config->get('bar'));
    }
    
    /** @test */
    public function the_default_gets_set_if_the_key_is_not_present_in_the_user_config()
    {
        $this->assertSame(null, $this->config->get('foo'));
        
        $this->config->extend('foo', 'bar');
        
        $this->assertEquals('bar', $this->config->get('foo'));
    }
    
    /** @test */
    public function user_config_has_precedence_over_default_config()
    {
        $this->assertSame(null, $this->config->get('foo'));
        
        $this->config->set('foo', 'bar');
        
        $this->assertSame('bar', $this->config->get('foo'));
        
        $this->config->extend('foo', 'baz');
        
        $this->assertSame('bar', $this->config->get('foo'));
    }
    
    /** @test */
    public function user_config_has_precedence_over_default_config_and_gets_merged_recursively()
    {
        $config = new Config([
            'foo' => [
                'foo' => 'foo',
                'bar' => 'bar',
                'baz' => [
                    'foo' => 'foo',
                ],
            ],
        ]);
        
        $config->extend('foo', [
            'bar' => 'foobarbaz',
            'baz' => [
                'bar' => 'bar',
            ],
            'foobarbaz' => 'foobarbaz',
        ]);
        
        $expected = [
            // Value is NOT missing.
            'foo' => 'foo',
            // Value is NOT replaced by default value.
            'bar' => 'bar',
            'baz' => [
                'foo' => 'foo',
                // Key from default is added in nested array.
                'bar' => 'bar',
            ],
            // Key from default is added.
            'foobarbaz' => 'foobarbaz',
        ];
        
        $this->assertSame($expected, $config->get('foo'));
    }
    
    /** @test */
    public function everything_works_with_dot_notation_as_well()
    {
        $config = new Config([
            'foo' => [
                'foo' => 'foo',
                'bar' => 'baz',
                'baz' => [
                    'biz' => 'boo',
                ],
            ],
        ]);
        
        $config->extend('foo.bar', 'biz');
        $this->assertEquals('baz', $config->get('foo.bar'));
        
        $config->extend('foo.boo', 'bam');
        $this->assertEquals('bam', $config->get('foo.boo'));
        
        $config->extend('foo.baz', ['bam' => 'boom']);
        $this->assertEquals(['biz' => 'boo', 'bam' => 'boom'], $config->get('foo.baz'));
        
        $config->extend('foo.baz.biz', 'bogus');
        $this->assertEquals('boo', $config->get('foo.baz.biz'));
    }
    
    /** @test */
    public function numerically_indexed_arrays_get_replaced()
    {
        $config = new Config([
            'first' => [
                'foo',
            ],
            'second' => [
                'foo' => [
                    'bar',
                    'baz',
                
                ],
            ],
        
        ]);
        
        $config->extend('first', ['foo', 'bar', 'baz']);
        
        $this->assertEquals(['foo', 'bar', 'baz'], $config->get('first'));
    }
    
    /** @test */
    public function test_extend_with_closure()
    {
        $config = new Config([
            'first' => [
                'foo' => 'foo',
                'bar' => false,
                'baz' => null,
            ],
            'second' => [
                'foo' => [
                    'bar',
                    'baz',
                
                ],
                'empty' => '',
                'spaces' => '     ',
            ],
            'third' => [
            
            ],
        
        ]);
        
        $config->extendIfEmpty('first', fn() => 'bar');
        $this->assertSame('foo', $config->get('first.foo'));
        
        // boolean false should be kept
        $config->extendIfEmpty('first.bar', fn() => true);
        $this->assertSame(false, $config->get('first.bar'));
        
        // null should be replaced.
        $config->extendIfEmpty('first.baz', fn() => true);
        $this->assertSame(true, $config->get('first.baz'));
        
        // empty array will be replaced
        $config->extendIfEmpty('third', fn() => true);
        $this->assertSame(true, $config->get('third'));
        
        // empty strings will be replaced
        $config->extendIfEmpty('second.empty', fn() => 'not-empty');
        $this->assertSame('not-empty', $config->get('second.empty'));
        
        // empty string with spaces will be replaced
        $config->extendIfEmpty('second.spaces', fn() => 'not-empty');
        $this->assertSame('not-empty', $config->get('second.spaces'));
        
        // test replace with config value
        $config->extendIfEmpty('second.foo.baz', fn($config) => $config->get('first.foo'));
        $this->assertSame('foo', $config->get('second.foo.baz'));
    }
    
    /** @test */
    public function testFromArray()
    {
        $config = new Config(['first' => ['foo' => 'bar']]);
        
        $this->assertSame('bar', $config->get('first.foo'));
        $this->assertNull($config->get('second.foo'));
        
        $config->seedFromCache([
            'first' => [
                'foo' => 'baz',
            ],
            'second' => [
                'foo' => [
                    'bar',
                    'baz',
                
                ],
            ],
        ]);
        
        // will be overwritten.
        $this->assertSame('baz', $config->get('first.foo'));
        $this->assertSame(['bar', 'baz'], $config->get('second.foo'));
    }
    
}

