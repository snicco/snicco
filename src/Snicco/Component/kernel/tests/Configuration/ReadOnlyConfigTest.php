<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\Configuration;

use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Kernel\Configuration\ReadOnlyConfig;
use Snicco\Component\Kernel\Exception\BadConfigType;
use Snicco\Component\Kernel\Exception\MissingConfigKey;

final class ReadOnlyConfigTest extends TestCase
{

    /**
     * @test
     */
    public function test_get(): void
    {
        $config = ReadOnlyConfig::fromArray(['foo' => 'bar', 'baz' => ['biz' => 'boo']]);

        $this->assertSame('bar', $config->get('foo'));
        $this->assertSame('boo', $config->get('baz.biz'));
    }

    /**
     * @test
     */
    public function test_get_on_missing_key_throws_exception(): void
    {
        $config = ReadOnlyConfig::fromArray(['foo' => 'bar', 'baz' => ['biz' => 'boo']]);

        $this->assertSame('bar', $config->get('foo'));

        $this->expectException(MissingConfigKey::class);
        $this->expectExceptionMessage('The key [bar] does not exist in the configuration.');

        $config->get('bar');
    }

    /**
     * @test
     */
    public function test_get_string(): void
    {
        $config = ReadOnlyConfig::fromArray(['foo' => 'bar', 'baz' => 1]);

        $this->assertSame('bar', $config->getString('foo'));

        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [baz] to be [string].\nGot [integer]."
        );

        $config->getString('baz');
    }

    /**
     * @test
     */
    public function test_get_int(): void
    {
        $config = ReadOnlyConfig::fromArray(['foo' => 1, 'baz' => 'biz']);

        $this->assertSame(1, $config->getInt('foo'));

        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [baz] to be [integer].\nGot [string]."
        );

        $config->getInt('baz');
    }

    /**
     * @test
     */
    public function test_get_array(): void
    {
        $config = ReadOnlyConfig::fromArray(['foo' => ['bar'], 'baz' => 'biz']);

        $this->assertSame(['bar'], $config->getArray('foo'));

        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [baz] to be [array].\nGot [string]."
        );

        $config->getArray('baz');
    }

    /**
     * @test
     */
    public function test_boolean(): void
    {
        $config =
            ReadOnlyConfig::fromArray(['foo' => ['bar' => true], 'baz' => false, 'biz' => 'boo']);

        $this->assertSame(false, $config->getBool('baz'));
        $this->assertSame(true, $config->getBool('foo.bar'));

        $this->expectException(BadConfigType::class);
        $this->expectExceptionMessage(
            "Expected config value for key [biz] to be [boolean].\nGot [string]."
        );

        $config->getBool('biz');
    }

    /**
     * @test
     */
    public function test_array_access_get_works(): void
    {
        $config = ReadOnlyConfig::fromArray(['foo' => ['bar' => 'baz']]);

        $this->assertSame('baz', $config['foo.bar']);
    }

    /**
     * @test
     */
    public function test_array_access_get_throws_for_missing_key(): void
    {
        $config = ReadOnlyConfig::fromArray(['foo' => ['bar' => 'baz']]);

        $this->expectException(MissingConfigKey::class);
        $this->expectExceptionMessage('The key [foo.biz] does not exist in the configuration.');

        $config['foo.biz'];
    }

    /**
     * @test
     */
    public function test_array_access_isset_works(): void
    {
        $config = ReadOnlyConfig::fromArray(['foo' => ['bar' => 'baz']]);

        $this->assertTrue(isset($config['foo.bar']));
        $this->assertFalse(isset($config['foo.biz']));
    }

    /**
     * @test
     */
    public function test_array_access_set_throws(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The configuration is read-only and cannot be changed.');

        $config = ReadOnlyConfig::fromArray([]);
        $config['foo'] = 'bar';
    }

    /**
     * @test
     */
    public function test_array_access_unset_throws(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The configuration is read-only and cannot be changed.');

        $config = ReadOnlyConfig::fromArray([]);
        unset($config['foo']);
    }

}