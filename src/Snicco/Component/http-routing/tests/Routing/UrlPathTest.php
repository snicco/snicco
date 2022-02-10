<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\StrArr\Str;

class UrlPathTest extends TestCase
{

    /**
     * @dataProvider providePath
     *
     * @test
     */
    public function testFromString(string $path, string $expected): void
    {
        $path = UrlPath::fromString($path);
        $this->assertSame($expected, $path->asString());
    }

    /**
     * @test
     *
     * @dataProvider providePath
     */
    public function testWithTrailingSlash(string $path): void
    {
        $path = UrlPath::fromString($path);
        $this->assertSame('/foo/', $path->withTrailingSlash()->asString());
    }

    /**
     * @test
     *
     * @dataProvider providePath
     */
    public function testWithoutTrailingSlash(string $path): void
    {
        $path = UrlPath::fromString($path);
        $this->assertSame('/foo', $path->withoutTrailingSlash()->asString());
    }

    /**
     * @test
     */
    public function testWithoutTrailingSlashImmutable(): void
    {
        $path = UrlPath::fromString('foo');
        $path_new = $path->withoutTrailingSlash();

        $this->assertNotSame($path_new, $path);
    }

    /**
     * @test
     */
    public function testWithTrailingSlashImmutable(): void
    {
        $path = UrlPath::fromString('foo');
        $path_new = $path->withTrailingSlash();

        $this->assertNotSame($path_new, $path);
    }

    /**
     * @test
     *
     * @dataProvider prependProvider
     */
    public function testPrepend(string $prepend): void
    {
        $path = UrlPath::fromString('/foo');
        $this->assertSame('/' . trim($prepend, '/') . '/foo', $path->prepend($prepend)->asString());
    }

    /**
     * @test
     *
     * @dataProvider prependProvider
     */
    public function testAppend(string $append_path): void
    {
        $expected = Str::endsWith($append_path, '/')
            ? trim($append_path, '/') . '/'
            : trim(
                $append_path,
                '/'
            );

        $path = UrlPath::fromString('/foo');
        $this->assertSame('/foo/' . $expected, $path->append($append_path)->asString());
    }

    /**
     * @test
     */
    public function test_startsWith(): void
    {
        $path = UrlPath::fromString('/wp-admin/foo/bar');

        $this->assertTrue($path->startsWith('/wp-admin'));
        $this->assertTrue($path->startsWith('wp-admin'));
        $this->assertTrue($path->startsWith('/wp-admin/'));
        $this->assertTrue($path->startsWith('wp-admin/'));
        $this->assertTrue($path->startsWith('/wp-admin/foo'));
        $this->assertTrue($path->startsWith('/wp-admin/foo/'));
        $this->assertTrue($path->startsWith('/wp-admin/foo/bar'));
        $this->assertFalse($path->startsWith('/wp-admin/foo/bar/'));
        $this->assertFalse($path->startsWith('/wp-admin/foo/baz'));
    }

    /**
     * @test
     */
    public function test_equals_with_slash_only(): void
    {
        $path = UrlPath::fromString('/');
        $this->assertTrue($path->equals('/'));
        $this->expectException(InvalidArgumentException::class);
        $this->assertTrue($path->equals(''));
    }

    public function providePath(): array
    {
        return [
            ['foo', '/foo'],
            ['/foo', '/foo'],
            ['//foo', '/foo'],
            ['/foo/', '/foo/'],
            ['/foo//', '/foo/'],
        ];
    }

    public function prependProvider(): Generator
    {
        yield ['foo'];
        yield ['/foo'];
        yield ['//foo'];
        yield ['/foo/'];
        yield ['/foo//'];
    }

}