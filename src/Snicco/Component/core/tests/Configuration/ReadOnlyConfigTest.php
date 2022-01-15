<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Tests\Configuration;

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
        
        $this->assertSame('bar', $config->getString('foo'));
        
        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [baz] to be [string].\nGot [integer]."
        );
        
        $config->getString('baz');
    }
    
    /** @test */
    public function test_get_int()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => 1, 'baz' => 'biz']);
        
        $this->assertSame(1, $config->getInteger('foo'));
        
        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [baz] to be [integer].\nGot [string]."
        );
        
        $config->getInteger('baz');
    }
    
    /** @test */
    public function test_get_array()
    {
        $config = ReadOnlyConfig::fromArray(['foo' => ['bar'], 'baz' => 'biz']);
        
        $this->assertSame(['bar'], $config->getArray('foo'));
        
        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [baz] to be [array].\nGot [string]."
        );
        
        $config->getArray('baz');
    }
    
}