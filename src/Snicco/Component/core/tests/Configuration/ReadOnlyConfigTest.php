<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\Configuration;

use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Core\Exception\BadConfigType;
use Snicco\Component\Core\Exception\MissingConfigKey;
use Snicco\Component\Core\Configuration\ReadOnlyConfig;

final class ReadOnlyConfigTest extends TestCase
{
    
    /** @test */
    public function test_get()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => 'bar', 'baz' => ['biz' => 'boo']]);
        
        $this->assertSame('bar', $config->get('foo'));
        $this->assertSame('boo', $config->get('baz.biz'));
    }
    
    /** @test */
    public function test_get_on_missing_key_throws_exception()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => 'bar', 'baz' => ['biz' => 'boo']]);
        
        $this->assertSame('bar', $config->get('foo'));
        
        $this->expectException(MissingConfigKey::class);
        $this->expectExceptionMessage("The key [bar] does not exist in the configuration.");
        
        $config->get('bar');
    }
    
    /** @test */
    public function test_get_string()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => 'bar', 'baz' => 1]);
        
        $this->assertSame('bar', $config->string('foo'));
        
        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [baz] to be [string].\nGot [integer]."
        );
        
        $config->string('baz');
    }
    
    /** @test */
    public function test_get_int()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => 1, 'baz' => 'biz']);
        
        $this->assertSame(1, $config->integer('foo'));
        
        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [baz] to be [integer].\nGot [string]."
        );
        
        $config->integer('baz');
    }
    
    /** @test */
    public function test_get_array()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => ['bar'], 'baz' => 'biz']);
        
        $this->assertSame(['bar'], $config->array('foo'));
        
        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [baz] to be [array].\nGot [string]."
        );
        
        $config->array('baz');
    }
    
    /** @test */
    public function test_boolean()
    {
        $config =
            ReadOnlyConfig::fromArray(['foo' => ['bar' => true], 'baz' => false, 'biz' => 'boo']);
        
        $this->assertSame(false, $config->boolean('baz'));
        $this->assertSame(true, $config->boolean('foo.bar'));
        
        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [biz] to be [boolean].\nGot [string]."
        );
        
        $config->boolean('biz');
    }
    
    /** @test */
    public function test_array_access_get_works()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => ['bar' => 'baz']]);
        
        $this->assertSame('baz', $config['foo.bar']);
    }
    
    /** @test */
    public function test_array_access_get_throws_for_missing_key()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => ['bar' => 'baz']]);
        
        $this->expectException(MissingConfigKey::class);
        $this->expectExceptionMessage("The key [foo.biz] does not exist in the configuration.");
        
        $val = $config['foo.biz'];
    }
    
    /** @test */
    public function test_array_access_isset_works()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => ['bar' => 'baz']]);
        
        $this->assertTrue(isset($config['foo.bar']));
        $this->assertFalse(isset($config['foo.biz']));
    }
    
    /** @test */
    public function test_array_access_set_throws()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The configuration is read-only and cannot be changed.");
        
        $config = ReadOnlyConfig::fromArray([]);
        $config['foo'] = 'bar';
    }
    
    /** @test */
    public function test_array_access_unset_throws()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The configuration is read-only and cannot be changed.');
        
        $config = ReadOnlyConfig::fromArray([]);
        unset($config['foo']);
    }
    
}