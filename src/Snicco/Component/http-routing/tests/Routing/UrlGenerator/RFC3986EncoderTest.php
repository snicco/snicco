<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlGenerator;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;

use function array_keys;
use function implode;
use function rawurlencode;

/**
 * @internal
 */
final class RFC3986EncoderTest extends TestCase
{
    /**
     * @var array<string, string>
     */
    private const QUERY_FRAGMENT_EXTRA = [
        '/' => '%2F',
        '?' => '%3F',
    ];

    /**
     * @test
     */
    public function test_encode_path(): void
    {
        $allowed = array_keys(RFC3986Encoder::RFC3986_PCHARS);
        $path = '/foo/bar/münchen/' . implode('', $allowed);

        $res = (new RFC3986Encoder())->encodePath($path);

        $this->assertSame('/foo/bar/' . rawurlencode('münchen') . '/' . implode('', $allowed), $res);
    }

    /**
     * @test
     */
    public function test_encode_query_string(): void
    {
        $allowed = RFC3986Encoder::RFC3986_PCHARS + self::QUERY_FRAGMENT_EXTRA;
        unset($allowed['+'], $allowed['&'], $allowed['=']);
        $allowed = array_keys($allowed);

        $arg = 'münchen=&' . implode('', $allowed) . '+';

        $res = (new RFC3986Encoder())->encodeQuery([
            'foo' => $arg,
        ]);

        $expected = rawurlencode('münchen=&') . implode('', $allowed) . '+';
        $this->assertSame('foo=' . $expected, $res);
    }

    /**
     * @test
     */
    public function test_encode_empty_array_return_empty_string(): void
    {
        $res = (new RFC3986Encoder())->encodeQuery([]);
        $this->assertSame('', $res);
    }

    /**
     * @test
     */
    public function test_encode_fragment(): void
    {
        $allowed = array_keys(RFC3986Encoder::RFC3986_PCHARS + self::QUERY_FRAGMENT_EXTRA);

        $arg = 'münchen' . '+' . '=' . '&' . implode('', $allowed);

        $res = (new RFC3986Encoder())->encodeFragment($arg);

        $expected = rawurlencode('münchen') . '+' . '=' . '&' . implode('', $allowed);
        $this->assertSame($expected, $res);
    }

    /**
     * @test
     */
    public function plus_signs_are_allowed_in_the_query_and_fragment(): void
    {
        $encoder = new RFC3986Encoder();

        $this->assertSame('foo=bar+', $encoder->encodeQuery([
            'foo' => 'bar+',
        ]));
        $this->assertSame('section1+', $encoder->encodeFragment('section1+'));
    }

    /**
     * @test
     */
    public function question_marks_are_allowed_in_the_query_and_fragment(): void
    {
        $encoder = new RFC3986Encoder();

        $this->assertSame('foo=bar?', $encoder->encodeQuery([
            'foo' => 'bar?',
        ]));
        $this->assertSame('section1?', $encoder->encodeFragment('section1?'));
    }

    /**
     * @test
     */
    public function forward_slashes_are_allowed_in_the_query_and_fragment(): void
    {
        $encoder = new RFC3986Encoder();

        $this->assertSame('foo=bar/', $encoder->encodeQuery([
            'foo' => 'bar/',
        ]));
        $this->assertSame('section1/', $encoder->encodeFragment('section1/'));
    }

    /**
     * @test
     */
    public function by_default_question_marks_and_equals_signs_are_not_allowed_in_the_query(): void
    {
        $encoder = new RFC3986Encoder();
        $this->assertSame('foo=%26&bar=%3D', $encoder->encodeQuery([
            'foo' => '&',
            'bar' => '=',
        ]));
    }

    /**
     * @test
     */
    public function question_marks_and_equals_signs_can_be_allowed_in_the_query(): void
    {
        $encoder = new RFC3986Encoder([]);
        $this->assertSame('foo=&&bar==', $encoder->encodeQuery([
            'foo' => '&',
            'bar' => '=',
        ]));
    }
}
