<?php

/*
 * These tests are an extended version of the illuminate/str class tests.
 *
 * The illuminate/support package is licensed under the MIT License:
 * https://github.com/laravel/framework/blob/v8.35.1/LICENSE.md
 *
 * The MIT License (MIT)
 *
 * Copyright (c) Taylor Otwell
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the 'Software'),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

declare(strict_types=1);

namespace Snicco\Component\StrArr\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\StrArr\Str;

/**
 * @internal
 */
final class StrTest extends TestCase
{
    /**
     * @test
     */
    public function test_substr(): void
    {
        $this->assertSame('Ё', Str::substr('БГДЖИЛЁ', -1));
        $this->assertSame('ЛЁ', Str::substr('БГДЖИЛЁ', -2));
        $this->assertSame('И', Str::substr('БГДЖИЛЁ', -3, 1));
        $this->assertSame('ДЖИЛ', Str::substr('БГДЖИЛЁ', 2, -1));
        $this->assertEmpty(Str::substr('БГДЖИЛЁ', 4, -4));
        $this->assertSame('ИЛ', Str::substr('БГДЖИЛЁ', -3, -1));
        $this->assertSame('ГДЖИЛЁ', Str::substr('БГДЖИЛЁ', 1));
        $this->assertSame('ГДЖ', Str::substr('БГДЖИЛЁ', 1, 3));
        $this->assertSame('БГДЖ', Str::substr('БГДЖИЛЁ', 0, 4));
        $this->assertSame('Ё', Str::substr('БГДЖИЛЁ', -1, 1));
        $this->assertSame('', Str::substr('Б', 2));
    }

    /**
     * @test
     */
    public function test_contains(): void
    {
        $this->assertTrue(Str::contains('taylor', 'ylo'));
        $this->assertTrue(Str::contains('taylor', 'taylor'));
        $this->assertFalse(Str::contains('taylor', 'xxx'));
        $this->assertFalse(Str::contains('taylor', ''));
        $this->assertFalse(Str::contains('', ''));
        $this->assertFalse(Str::contains('taylor', ''));
        $this->assertFalse(Str::contains('', 'foo'));

        $this->assertTrue(Str::contains('düsseldorf', 'ü'));
        $this->assertFalse(Str::contains('düsseldorf', 'ö'));
    }

    /**
     * @test
     */
    public function test_contains_all(): void
    {
        $this->assertTrue(Str::containsAll('taylor otwell', ['taylor', 'otwell']));
        $this->assertTrue(Str::containsAll('taylor otwell', ['taylor']));
        $this->assertFalse(Str::containsAll('taylor otwell', ['taylor', 'xxx']));

        $this->assertTrue(Str::containsAll('düsseldorf', ['ü', 'o']));
        $this->assertFalse(Str::containsAll('düsseldorf', ['ö', 'o']));
    }

    /**
     * @test
     */
    public function test_contains_any(): void
    {
        $this->assertTrue(Str::containsAny('taylor otwell', ['taylor', 'otwell']));
        $this->assertTrue(Str::containsAny('taylor otwell', ['taylor']));
        $this->assertTrue(Str::containsAny('taylor otwell', ['taylor', 'xxx']));
        $this->assertFalse(Str::containsAny('taylor otwell', ['yyy', 'xxx']));

        $this->assertTrue(Str::containsAny('düsseldorf', ['ü', 'ö']));
        $this->assertFalse(Str::containsAny('düsseldorf', ['ä', 'ö']));
    }

    /**
     * @test
     */
    public function test_studly(): void
    {
        $this->assertSame('LaravelPHPFramework', Str::studly('laravel_p_h_p_framework'));
        $this->assertSame('LaravelPhpFramework', Str::studly('laravel_php_framework'));
        $this->assertSame('LaravelPhPFramework', Str::studly('laravel-phP-framework'));
        $this->assertSame('LaravelPhpFramework', Str::studly('laravel  -_-  php   -_-   framework   '));

        $this->assertSame('FooBar', Str::studly('fooBar'));
        $this->assertSame('FooBar', Str::studly('foo_bar'));
        $this->assertSame('FooBar', Str::studly('foo_bar')); // test cache
        $this->assertSame('FooBarBaz', Str::studly('foo-barBaz'));
        $this->assertSame('FooBarBaz', Str::studly('foo-bar_baz'));

        $this->assertSame('Düsseldorf', Str::studly('düsseldorf'));
        $this->assertSame('DÜsseldorf', Str::studly('d_üsseldorf'));
    }

    /**
     * @test
     */
    public function test_ends_with(): void
    {
        $this->assertTrue(Str::endsWith('jason', 'on'));
        $this->assertTrue(Str::endsWith('jason', 'jason'));
        $this->assertFalse(Str::endsWith('jason', 'no'));
        $this->assertFalse(Str::endsWith('jason', ''));
        $this->assertFalse(Str::endsWith('', ''));
        $this->assertFalse(Str::endsWith('jason', 'N'));
        $this->assertFalse(Str::endsWith('7', ' 7'));
        $this->assertTrue(Str::endsWith('a7', '7'));
        // Test for multibyte string support
        $this->assertTrue(Str::endsWith('Jönköping', 'öping'));
        $this->assertTrue(Str::endsWith('Malmö', 'mö'));
        $this->assertFalse(Str::endsWith('Jönköping', 'oping'));
        $this->assertFalse(Str::endsWith('Malmö', 'mo'));
        $this->assertTrue(Str::endsWith('你好', '好'));
        $this->assertFalse(Str::endsWith('你好', '你'));
        $this->assertFalse(Str::endsWith('你好', 'a'));
    }

    /**
     * @test
     */
    public function test_does_not_end_with(): void
    {
        $this->assertFalse(Str::doesNotEndWith('jason', 'on'));
        $this->assertFalse(Str::doesNotEndWith('jason', 'jason'));
        $this->assertTrue(Str::doesNotEndWith('jason', 'no'));
        $this->assertTrue(Str::doesNotEndWith('jason', ''));
        $this->assertTrue(Str::doesNotEndWith('', ''));
        $this->assertTrue(Str::doesNotEndWith('jason', 'N'));
        $this->assertTrue(Str::doesNotEndWith('7', ' 7'));
        $this->assertFalse(Str::doesNotEndWith('a7', '7'));
        // Test for multibyte string support
        $this->assertFalse(Str::doesNotEndWith('Jönköping', 'öping'));
        $this->assertFalse(Str::doesNotEndWith('Malmö', 'mö'));
        $this->assertTrue(Str::doesNotEndWith('Jönköping', 'oping'));
        $this->assertTrue(Str::doesNotEndWith('Malmö', 'mo'));
        $this->assertFalse(Str::doesNotEndWith('你好', '好'));
        $this->assertTrue(Str::doesNotEndWith('你好', '你'));
        $this->assertTrue(Str::doesNotEndWith('你好', 'a'));
    }

    /**
     * @test
     */
    public function test_after(): void
    {
        $this->assertSame('nah', Str::afterFirst('hannah', 'han'));
        $this->assertSame('nah', Str::afterFirst('hannah', 'n'));
        $this->assertSame('nah', Str::afterFirst('ééé hannah', 'han'));
        $this->assertSame('hannah', Str::afterFirst('hannah', 'xxxx'));
        $this->assertSame('hannah', Str::afterFirst('hannah', ''));
        $this->assertSame('nah', Str::afterFirst('han0nah', '0'));
        $this->assertSame('han0nah', Str::afterFirst('han0nah', ''));
        $this->assertSame('nah', Str::afterFirst('han2nah', '2'));
        $this->assertSame('sseldorf', Str::afterFirst('düsseldorf', 'ü'));
        $this->assertSame('öping', Str::afterFirst('Jönköping', 'k'));

        $this->assertSame('ööööööxy', Str::afterFirst('öööööööxy', 'ö'));
    }

    /**
     * @test
     */
    public function test_after_last(): void
    {
        $this->assertSame('tte', Str::afterLast('yvette', 'yve'));
        $this->assertSame('e', Str::afterLast('yvette', 't'));
        $this->assertSame('e', Str::afterLast('ééé yvette', 't'));
        $this->assertSame('', Str::afterLast('yvette', 'tte'));
        $this->assertSame('yvette', Str::afterLast('yvette', 'xxxx'));
        $this->assertSame('yvette', Str::afterLast('yvette', ''));
        $this->assertSame('te', Str::afterLast('yv0et0te', '0'));
        $this->assertSame('foo', Str::afterLast('----foo', '---'));

        $this->assertSame('xy', Str::afterLast('öööööööxy', 'ö'));

        $this->assertSame('yvette', Str::afterLast('éééyvette', 'é'));
    }

    /**
     * @test
     */
    public function test_starts_with(): void
    {
        $this->assertTrue(Str::startsWith('jason', 'jas'));
        $this->assertTrue(Str::startsWith('jason', 'jason'));
        $this->assertFalse(Str::startsWith('jason', 'day'));
        $this->assertFalse(Str::startsWith('jason', 'J'));
        $this->assertFalse(Str::startsWith('jason', ''));
        $this->assertFalse(Str::startsWith('', ''));
        $this->assertFalse(Str::startsWith('7', ' 7'));
        $this->assertTrue(Str::startsWith('7a', '7'));
        // Test for multibyte string support
        $this->assertTrue(Str::startsWith('Jönköping', 'Jö'));
        $this->assertTrue(Str::startsWith('Malmö', 'Malmö'));
        $this->assertFalse(Str::startsWith('Jönköping', 'Jonko'));
        $this->assertFalse(Str::startsWith('Malmö', 'Malmo'));
        $this->assertTrue(Str::startsWith('你好', '你'));
        $this->assertFalse(Str::startsWith('你好', '好'));
        $this->assertFalse(Str::startsWith('你好', 'a'));
    }

    /**
     * @test
     */
    public function test_uc_first(): void
    {
        $this->assertSame('Snicco', Str::ucfirst('Snicco'));
        $this->assertSame('Snicco framework', Str::ucfirst('snicco framework'));
        $this->assertSame('Мама', Str::ucfirst('мама'));
        $this->assertSame('Мама мыла раму', Str::ucfirst('мама мыла раму'));
        $this->assertSame('Über', Str::ucfirst('über'));
        $this->assertSame('Düsseldorf', Str::ucfirst('düsseldorf'));
    }

    /**
     * @test
     */
    public function test_between(): void
    {
        $this->assertSame('', Str::betweenLast('abc', 'a', 'b'));
        $this->assertSame('abc', Str::betweenLast('abc', '', 'c'));
        $this->assertSame('abc', Str::betweenLast('abc', 'a', ''));
        $this->assertSame('abc', Str::betweenLast('abc', '', ''));
        $this->assertSame('b', Str::betweenLast('abc', 'a', 'c'));
        $this->assertSame('b', Str::betweenLast('dddabc', 'a', 'c'));
        $this->assertSame('b', Str::betweenLast('abcddd', 'a', 'c'));
        $this->assertSame('b', Str::betweenLast('dddabcddd', 'a', 'c'));
        $this->assertSame('nn', Str::betweenLast('hannah', 'ha', 'ah'));
        $this->assertSame('a]ab[b', Str::betweenLast('[a]ab[b]', '[', ']'));
        $this->assertSame('foo', Str::betweenLast('foofoobar', 'foo', 'bar'));
        $this->assertSame('bar', Str::betweenLast('foobarbar', 'foo', 'bar'));

        // Always between first and last occurrence
        $this->assertSame('XXYY', Str::betweenLast('XXXYYY', 'X', 'Y'));

        // multibyte
        $this->assertSame('', Str::betweenLast('münchen', 'm', 'ü'));

        $this->assertSame('xä', Str::betweenLast('öxäü', 'ö', 'ü'));
    }

    /**
     * @test
     */
    public function test_between_first(): void
    {
        $this->assertSame('', Str::betweenFirst('abc', 'a', 'b'));
        $this->assertSame('abc', Str::betweenFirst('abc', '', 'c'));
        $this->assertSame('abc', Str::betweenFirst('abc', 'a', ''));
        $this->assertSame('abc', Str::betweenFirst('abc', '', ''));
        $this->assertSame('b', Str::betweenFirst('abc', 'a', 'c'));
        $this->assertSame('b', Str::betweenFirst('dddabc', 'a', 'c'));
        $this->assertSame('b', Str::betweenFirst('abcddd', 'a', 'c'));
        $this->assertSame('b', Str::betweenFirst('dddabcddd', 'a', 'c'));
        $this->assertSame('nn', Str::betweenFirst('hannah', 'ha', 'ah'));
        $this->assertSame('a', Str::betweenFirst('[a]ab[b]', '[', ']'));
        $this->assertSame('foo', Str::betweenFirst('foofoobar', 'foo', 'bar'));
        $this->assertSame('', Str::betweenFirst('foobarbar', 'foo', 'bar'));

        $this->assertSame('XX', Str::betweenFirst('XXXYYY', 'X', 'Y'));

        // multibyte
        $this->assertSame('', Str::betweenFirst('münchen', 'm', 'ü'));

        $this->assertSame('ääääx', Str::betweenFirst('äääääxööööö', 'ä', 'ö'));
    }

    /**
     * @test
     */
    public function test_before_last(): void
    {
        $this->assertSame('yve', Str::beforeLast('yvette', 'tte'));
        $this->assertSame('yvet', Str::beforeLast('yvette', 't'));
        $this->assertSame('ééé ', Str::beforeLast('ééé yvette', 'yve'));
        $this->assertSame('', Str::beforeLast('yvette', 'yve'));
        $this->assertSame('yvette', Str::beforeLast('yvette', 'xxxx'));
        $this->assertSame('yvette', Str::beforeLast('yvette', ''));
        $this->assertSame('yv0et', Str::beforeLast('yv0et0te', '0'));

        $this->assertSame('üäöö', Str::beforeLast('üäööö', 'ö'));
    }

    /**
     * @test
     */
    public function test_before(): void
    {
        $this->assertSame('han', Str::beforeFirst('hannah', 'nah'));
        $this->assertSame('ha', Str::beforeFirst('hannah', 'n'));
        $this->assertSame('ééé ', Str::beforeFirst('ééé hannah', 'han'));
        $this->assertSame('hannah', Str::beforeFirst('hannah', 'xxxx'));
        $this->assertSame('hannah', Str::beforeFirst('hannah', ''));
        $this->assertSame('han', Str::beforeFirst('han0nah', '0'));

        $this->assertSame('üä', Str::beforeFirst('üäööö', 'ö'));

        $this->assertSame('J', Str::beforeFirst('Jönköping', 'ö'));
        $this->assertSame('Malm', Str::beforeFirst('Malmö', 'ö'));
        $this->assertSame('你', Str::beforeFirst('你好好好好好好', '好'));
    }

    /**
     * @test
     */
    public function test_is(): void
    {
        $this->assertTrue(Str::is('/', '/'));
        $this->assertFalse(Str::is(' /', '/'));
        $this->assertFalse(Str::is('/a', '/'));
        $this->assertTrue(Str::is('foo/bar/baz', 'foo/*'));

        $this->assertTrue(Str::is('App\Class@method', '*@*'));
        $this->assertTrue(Str::is('app\Class@', '*@*'));
        $this->assertTrue(Str::is('@method', '*@*'));

        // is case-sensitive
        $this->assertFalse(Str::is('foo/bar/baz', '*BAZ*'));
        $this->assertFalse(Str::is('foo/bar/baz', '*FOO*'));
        $this->assertFalse(Str::is('a', 'A'));

        $this->assertTrue(Str::is('blah/baz/foo', '*/foo'));

        // mb
        $this->assertTrue(Str::is('Düsseldorf', 'Dü*'));
        $this->assertFalse(Str::is('Dusseldorf', 'Dü*'));
    }

    /**
     * @test
     */
    public function replace_first(): void
    {
        $this->assertSame('fooqux foobar', Str::replaceFirst('foobar foobar', 'bar', 'qux'));
        $this->assertSame('foo/qux? foo/bar?', Str::replaceFirst('foo/bar? foo/bar?', 'bar?', 'qux?'));
        $this->assertSame('foo foobar', Str::replaceFirst('foobar foobar', 'bar', ''));
        $this->assertSame('foobar foobar', Str::replaceFirst('foobar foobar', 'xxx', 'yyy'));
        $this->assertSame('foobar foobar', Str::replaceFirst('foobar foobar', '', 'foo'));

        // Test for multibyte string support
        $this->assertSame('Jxxxnköping Malmö', Str::replaceFirst('Jönköping Malmö', 'ö', 'xxx'));
        $this->assertSame('Jönköping Malmö', Str::replaceFirst('Jönköping Malmö', '', 'yyy'));

        $this->assertSame('xxx你你好', Str::replaceFirst('你你你好', '你', 'xxx'));
        $this->assertSame('好你好好', Str::replaceFirst('你你好好', '你', '好'));
    }

    /**
     * @test
     */
    public function replace_all(): void
    {
        $this->assertSame('fooqux fooqux', Str::replaceAll('foobar foobar', 'bar', 'qux'));
        $this->assertSame('foo/qux? foo/qux?', Str::replaceAll('foo/bar? foo/bar?', 'bar?', 'qux?'));
        $this->assertSame('foo foo', Str::replaceAll('foobar foobar', 'bar', ''));
        $this->assertSame('foobar foobar', Str::replaceAll('foobar foobar', 'xxx', 'yyy'));
        $this->assertSame('foobar foobar', Str::replaceAll('foobar foobar', '', 'foo'));

        // Test for multibyte string support
        $this->assertSame('Jxxxnkxxxping Malmxxx', Str::replaceAll('Jönköping Malmö', 'ö', 'xxx'));
        $this->assertSame('Jönköping Malmö', Str::replaceAll('Jönköping Malmö', '', 'yyy'));

        $this->assertSame('xxx_xxx_好xxx_', Str::replaceAll('你你好你', '你', 'xxx_'));
        $this->assertSame('好好好好', Str::replaceAll('你你好好', '你', '好'));
    }

    /**
     * @test
     */
    public function test_preg_replace(): void
    {
        $str = '.....foo.....';

        $this->assertSame('foo', Str::pregReplace($str, '/^\.+|\.+$/', ''));

        $this->assertSame('Malmo', Str::pregReplace('Malmööö', '/[ö].+/', 'o'));
        $this->assertSame('Malmooo', Str::pregReplace('Malmööö', '/[ö]/', 'o',));
    }

    /**
     * @test
     */
    public function test_preg_replace_error_for_malformed_utf8(): void
    {
        try {
            Str::pregReplace("0123456789\xFF", '/\d/', 'x');
            $this->fail('No exception thrown for bad regex.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('preg_replace failed', $e->getMessage());
            $this->assertStringContainsString('Pattern: [/\d/]', $e->getMessage());
            $this->assertStringContainsString("Subject: [0123456789\xFF]", $e->getMessage());
        }
    }
}
