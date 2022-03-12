<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use Generator;
use InvalidArgumentException;
use Iterator;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\StrArr\Str;

/**
 * @internal
 */
final class UrlPathTest extends TestCase
{
    /**
     * @dataProvider providePath
     *
     * @test
     */
    public function test__from_string(string $path, string $expected): void
    {
        $path = UrlPath::fromString($path);
        $this->assertSame($expected, $path->asString());
    }

    /**
     * @test
     *
     * @dataProvider providePath
     */
    public function test_with_trailing_slash(string $path): void
    {
        $path = UrlPath::fromString($path);
        $this->assertSame('/foo/', $path->withTrailingSlash()->asString());
    }

    /**
     * @test
     *
     * @dataProvider providePath
     */
    public function test_without_trailing_slash(string $path): void
    {
        $path = UrlPath::fromString($path);
        $this->assertSame('/foo', $path->withoutTrailingSlash()->asString());
    }

    /**
     * @test
     */
    public function test_without_trailing_slash_immutable(): void
    {
        $path = UrlPath::fromString('foo');
        $path_new = $path->withoutTrailingSlash();

        $this->assertNotSame($path_new, $path);
    }

    /**
     * @test
     */
    public function test_with_trailing_slash_immutable(): void
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
    public function test_prepend(string $prepend): void
    {
        $path = UrlPath::fromString('/foo');
        $this->assertSame('/' . trim($prepend, '/') . '/foo', $path->prepend($prepend)->asString());
    }

    /**
     * @test
     *
     * @dataProvider prependProvider
     */
    public function test_append(string $append_path): void
    {
        $expected = Str::endsWith($append_path, '/')
            ? trim($append_path, '/') . '/'
            : trim($append_path, '/');

        $path = UrlPath::fromString('/foo');
        $this->assertSame('/foo/' . $expected, $path->append($append_path)->asString());
    }

    /**
     * @test
     */
    public function test_starts_with(): void
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

    /**
     * @test
     */
    public function test_contains(): void
    {
        $path = UrlPath::fromString('/foo/bar/baz');

        $this->assertTrue($path->contains('/foo'));
        $this->assertTrue($path->contains('/foo/bar/baz'));
        $this->assertFalse($path->contains('/foo/bar/baz/'));
        $this->assertFalse($path->contains('biz'));
    }

    public function providePath(): Iterator
    {
        yield ['foo', '/foo'];
        yield ['/foo', '/foo'];
        yield ['//foo', '/foo'];
        yield ['/foo/', '/foo/'];
        yield ['/foo//', '/foo/'];
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
