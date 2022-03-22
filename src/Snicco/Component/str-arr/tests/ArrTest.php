<?php

/*
 * These tests are an extended version of the illuminate/Arr class tests.
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

use ArrayAccess;
use ArrayObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReturnTypeWillChange;
use Snicco\Component\StrArr\Arr;
use stdClass;

use function array_key_exists;

/**
 * @internal
 */
final class ArrTest extends TestCase
{
    /**
     * @test
     */
    public function test_get(): void
    {
        $this->assertSame('bar', Arr::get([
            'foo' => 'bar',
        ], 'foo'));
        $this->assertSame('default', Arr::get([
            'foo' => 'bar',
        ], 'bogus', 'default'));

        $array = [
            'products.desk' => [
                'price' => 100,
            ],
        ];
        $this->assertEquals([
            'price' => 100,
        ], Arr::get($array, 'products.desk'));

        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        $this->assertEquals([
            'price' => 100,
        ], Arr::get($array, 'products.desk'));

        // Test null array values
        $array = [
            'foo' => null,
            'bar' => [
                'baz' => null,
            ],
        ];
        $this->assertNull(Arr::get($array, 'foo', 'default'));
        $this->assertNull(Arr::get($array, 'bar.baz', 'default'));

        // Test direct ArrayAccess object
        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        $arrayAccessObject = new ArrayObject($array);
        $this->assertEquals([
            'price' => 100,
        ], Arr::get($arrayAccessObject, 'products.desk'));

        // Test array containing ArrayAccess object
        $arrayAccessChild = new ArrayObject([
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ]);
        $array = [
            'child' => $arrayAccessChild,
        ];
        $this->assertEquals([
            'price' => 100,
        ], Arr::get($array, 'child.products.desk'));

        // Test array containing multiple nested ArrayAccess objects
        $arrayAccessChild = new ArrayObject([
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ]);
        $arrayAccessParent = new ArrayObject([
            'child' => $arrayAccessChild,
        ]);
        $array = [
            'parent' => $arrayAccessParent,
        ];
        $this->assertEquals([
            'price' => 100,
        ], Arr::get($array, 'parent.child.products.desk'));

        // Test missing ArrayAccess object field
        $arrayAccessChild = new ArrayObject([
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ]);
        $arrayAccessParent = new ArrayObject([
            'child' => $arrayAccessChild,
        ]);
        $array = [
            'parent' => $arrayAccessParent,
        ];
        // products key is missing
        $this->assertNull(Arr::get($array, 'parent.child.desk'));

        // Test missing ArrayAccess object field
        $arrayAccessObject = new ArrayObject([
            'products' => [
                'desk' => null,
            ],
        ]);
        $array = [
            'parent' => $arrayAccessObject,
        ];
        $this->assertNull(Arr::get($array, 'parent.products.desk.price'));

        // Test null ArrayAccess object fields
        $array = new ArrayObject([
            'foo' => null,
            'bar' => new ArrayObject([
                'baz' => null,
            ]),
        ]);
        $this->assertNull(Arr::get($array, 'foo', 'default'));
        $this->assertNull(Arr::get($array, 'bar.baz', 'default'));

        // Test numeric keys
        $array = [
            'products' => [
                [
                    'name' => 'desk',
                ],
                [
                    'name' => 'chair',
                ],
            ],
        ];
        $this->assertSame('desk', Arr::get($array, 'products.0.name'));
        $this->assertSame('chair', Arr::get($array, 'products.1.name'));

        // Test return default value for non-existing key.
        $array = [
            'names' => [
                'developer' => 'taylor',
            ],
        ];
        $this->assertSame('dayle', Arr::get($array, 'names.otherDeveloper', 'dayle'));
        $this->assertSame('dayle', Arr::get($array, 'names.otherDeveloper', fn (): string => 'dayle'));

        // test keys with dots have priority
        $array = [
            'foo.bar' => [
                'bar' => 'baz',
            ],
        ];
        $this->assertSame([
            'bar' => 'baz',
        ], Arr::get($array, 'foo.bar'));
    }

    /**
     * @test
     */
    public function test_exists(): void
    {
        $this->assertTrue(Arr::keyExists([1], 0));
        $this->assertTrue(Arr::keyExists([null], 0));
        $this->assertTrue(Arr::keyExists([
            'a' => 1,
        ], 'a'));
        $this->assertTrue(Arr::keyExists([
            'a' => null,
        ], 'a'));
        $this->assertTrue(Arr::keyExists(new ArrayObject([
            'a' => null,
        ]), 'a'));

        $this->assertFalse(Arr::keyExists([1], 1));
        $this->assertFalse(Arr::keyExists([null], 1));
        $this->assertFalse(Arr::keyExists([
            'a' => 1,
        ], 0));
        $this->assertFalse(Arr::keyExists(new ArrayObject([
            'a' => null,
        ]), 'b'));
    }

    /**
     * @test
     */
    public function test_to_array(): void
    {
        $string = 'a';
        $array = ['a'];
        $object = new stdClass();
        $object->value = 'a';
        $this->assertEquals(['a'], Arr::toArray($string));
        $this->assertEquals($array, Arr::toArray($array));
        $this->assertEquals([$object], Arr::toArray($object));

        // This is modified from the original
        $this->assertEquals([null], Arr::toArray(null));

        $this->assertEquals([null], Arr::toArray([null]));
        $this->assertEquals([null, null], Arr::toArray([null, null]));
        $this->assertEquals([''], Arr::toArray(''));
        $this->assertEquals([''], Arr::toArray(['']));
        $this->assertEquals([false], Arr::toArray(false));
        $this->assertEquals([false], Arr::toArray([false]));
        $this->assertEquals([0], Arr::toArray(0));

        $obj = new stdClass();
        $obj->value = 'a';
        /** @var stdClass $obj */
        $obj = unserialize(serialize($obj));
        $this->assertEquals([$obj], Arr::toArray($obj));
        $this->assertSame($obj, Arr::toArray($obj)[0]);
    }

    /**
     * @test
     */
    public function test_only(): void
    {
        $array = [
            'name' => 'Desk',
            'price' => 100,
            'orders' => 10,
        ];
        $array = Arr::only($array, ['name', 'price']);
        $this->assertEquals([
            'name' => 'Desk',
            'price' => 100,
        ], $array);
        $this->assertEmpty(Arr::only($array, ['nonExistingKey']));
    }

    /**
     * @test
     */
    public function test_accessible(): void
    {
        $this->assertTrue(Arr::accessible([]));
        $this->assertTrue(Arr::accessible([1, 2]));
        $this->assertTrue(Arr::accessible([
            'a' => 1,
            'b' => 2,
        ]));
        $this->assertTrue(Arr::accessible(new ArrayObject()));

        $this->assertFalse(Arr::accessible(null));
        $this->assertFalse(Arr::accessible('abc'));
        $this->assertFalse(Arr::accessible(new stdClass()));
        $this->assertFalse(Arr::accessible((object) [
            'a' => 1,
            'b' => 2,
        ]));
    }

    /**
     * @test
     */
    public function test_first(): void
    {
        $array = [100, 200, 300];

        // Callback is null and array is empty
        $this->assertNull(Arr::first([]));
        $this->assertSame('foo', Arr::first([], null, 'foo'));

        $this->assertSame('bar', Arr::first([], null, 'bar'));

        // Callback is null and array is not empty
        $this->assertSame(100, Arr::first($array));

        // Callback is not null and array is not empty
        $value = Arr::first($array, fn (int $value): bool => $value >= 150);
        $this->assertSame(200, $value);

        // Callback is not null, array is not empty but no satisfied item
        $value2 = Arr::first($array, fn (int $value): bool => $value > 300);

        $value3 = Arr::first($array, fn (int $value): bool => $value > 300, 500);

        $value4 = Arr::first($array, fn (int $value): bool => $value > 300, 600);

        $this->assertNull($value2);
        $this->assertSame(500, $value3);
        $this->assertSame(600, $value4);

        $mixed_array = [
            100,
            200,
            300,
            'foo' => 'bar',
        ];

        $value = Arr::first($mixed_array, fn ($value): bool => 'bar' === $value);
        $this->assertSame('bar', $value);
    }

    /**
     * @test
     */
    public function test_random(): void
    {
        $random = Arr::random(['foo', 'bar', 'baz']);
        $this->assertContains($random, ['foo', 'bar', 'baz']);

        $random = Arr::random(['foo', 'bar', 'baz'], 2);
        $this->assertCount(2, $random);
        $this->assertTrue(isset($random[0]));
        $this->assertTrue(isset($random[1]));
        $this->assertContains($random[0], ['foo', 'bar', 'baz']);
        $this->assertContains($random[1], ['foo', 'bar', 'baz']);

        // exception for count bigger than available
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You requested [4] items, but there are only [3] items available.');
        Arr::random(['foo', 'bar', 'baz'], 4);
    }

    /**
     * @test
     */
    public function test_random_throws_for_empty_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$array cant be empty.');
        Arr::random([]);
    }

    /**
     * @test
     */
    public function test_random_throws_for_count_small_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$number must be > 1');
        /** @psalm-suppress InvalidArgument */
        Arr::random(['foo', 'bar', 'baz'], 0);
    }

    /**
     * @test
     */
    public function test_remove(): void
    {
        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        Arr::remove($array, []);
        $this->assertEquals([
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ], $array);

        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        Arr::remove($array, 'products.desk');
        $this->assertEquals([
            'products' => [],
        ], $array);

        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        Arr::remove($array, 'products.desk.price');
        $this->assertEquals([
            'products' => [
                'desk' => [],
            ],
        ], $array);

        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        Arr::remove($array, 'products.final.price');
        $this->assertEquals([
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ], $array);

        $array = [
            'shop' => [
                'cart' => [
                    150 => 0,
                ],
            ],
        ];
        Arr::remove($array, 'shop.final.cart');
        $this->assertEquals([
            'shop' => [
                'cart' => [
                    150 => 0,
                ],
            ],
        ], $array);

        $array = [
            'products' => [
                'desk' => [
                    'price' => [
                        'original' => 50,
                        'taxes' => 60,
                    ],
                ],
            ],
        ];
        Arr::remove($array, 'products.desk.price.taxes');
        $this->assertEquals([
            'products' => [
                'desk' => [
                    'price' => [
                        'original' => 50,
                    ],
                ],
            ],
        ], $array);

        $array = [
            'products' => [
                'desk' => [
                    'price' => [
                        'original' => 50,
                        'taxes' => 60,
                    ],
                ],
            ],
        ];
        Arr::remove($array, 'products.desk.final.taxes');
        $this->assertEquals(
            [
                'products' => [
                    'desk' => [
                        'price' => [
                            'original' => 50,
                            'taxes' => 60,
                        ],
                    ],
                ],
            ],
            $array
        );

        $array = [
            'products' => [
                'desk' => [
                    'price' => 50,
                ],
                null => 'something',
            ],
        ];
        Arr::remove($array, ['products.amount.all', 'products.desk.price']);
        $this->assertEquals([
            'products' => [
                'desk' => [],
                null => 'something',
            ],
        ], $array);

        // Only works on first level keys
        $array = [
            'joe@example.com' => 'Joe',
            'jane@example.com' => 'Jane',
        ];
        Arr::remove($array, 'joe@example.com');
        $this->assertEquals([
            'jane@example.com' => 'Jane',
        ], $array);

        // Does not work for nested keys
        $array = [
            'emails' => [
                'joe@example.com' => [
                    'name' => 'Joe',
                ],
                'jane@localhost' => [
                    'name' => 'Jane',
                ],
            ],
        ];
        Arr::remove($array, ['emails.joe@example.com', 'emails.jane@localhost']);
        $this->assertEquals([
            'emails' => [
                'joe@example.com' => [
                    'name' => 'Joe',
                ],
            ],
        ], $array);
    }

    /**
     * @test
     */
    public function test_collapse(): void
    {
        $data = [['foo', 'bar'], ['baz']];
        $this->assertEquals(['foo', 'bar', 'baz'], Arr::collapse($data));

        $array = [[1], [2], [3], ['foo', 'bar'], new ArrayObject(['baz', 'boom'])];
        $this->assertEquals([1, 2, 3, 'foo', 'bar', 'baz', 'boom'], Arr::collapse($array));
    }

    /**
     * @test
     */
    public function test_except(): void
    {
        $array = [
            'foo' => 'bar',
            'baz' => 'biz',
        ];

        $this->assertEquals([
            'baz' => 'biz',
        ], Arr::except($array, ['foo']));

        $this->assertEquals([
            'baz' => 'biz',
        ], Arr::except($array, 'foo'));

        // Original stays the same.
        $this->assertSame($array, [
            'foo' => 'bar',
            'baz' => 'biz',
        ]);

        $array = [
            'foo' => 'bar',
            'baz' => [
                'biz' => 'boo',
                'bang' => 'boom',
            ],
        ];

        $this->assertEquals([
            'foo' => 'bar',
        ], Arr::except($array, 'baz'));

        $this->assertEquals([
            'foo' => 'bar',
            'baz' => [
                'bang' => 'boom',
            ],
        ], Arr::except($array, 'baz.biz'));

        $this->assertEquals(
            [
                'foo' => 'bar',
                'baz' => [
                    'biz' => 'boo',
                ],
            ],
            Arr::except($array, ['name', 'baz.bang'])
        );

        // Original stays the same.
        $this->assertSame($array, [
            'foo' => 'bar',
            'baz' => [
                'biz' => 'boo',
                'bang' => 'boom',
            ],
        ]);
    }

    /**
     * @test
     */
    public function test_flatten(): void
    {
        // Flat arrays are unaffected
        $array = ['#foo', '#bar', '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array, 1));

        // with depth
        $array = [
            [
                'foo' => 'bar',
                'baz' => ['biz'],
            ],
            [
                'boo' => 'bam',
            ],
        ];
        $this->assertEquals(['bar', 'biz', 'bam'], Arr::flatten($array));
        $this->assertEquals(['bar', ['biz'], 'bam'], Arr::flatten($array, 1));

        $array = ['#foo', '#bar', '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array, 5000));

        // Nested arrays are flattened with existing flat items
        $array = [['#foo', '#bar'], '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Flattened array includes "null" items
        $array = [['#foo', null], '#baz', null];
        $this->assertEquals(['#foo', null, '#baz', null], Arr::flatten($array));

        // Sets of nested arrays are flattened
        $array = [['#foo', '#bar'], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Deeply nested arrays are flattened
        $array = [['#foo', ['#bar']], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Nested arrays are flattened alongside arrays
        $array = [new ArrayObject(['#foo', '#bar']), ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Nested arrays containing plain arrays are flattened
        $array = [new ArrayObject(['#foo', ['#bar']]), ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Nested arrays containing arrays are flattened
        $array = [['#foo', new ArrayObject(['#bar'])], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        // Nested arrays containing arrays are flattened
        $array = [
            [
                '#foo',
                new ArrayObject([
                    '#bar',
                    [
                        'foo' => '#zap',
                    ],
                ]),
            ],
            ['#baz'],
        ];
        $this->assertEquals(['#foo', '#bar', '#zap', '#baz'], Arr::flatten($array));
    }

    /**
     * @test
     */
    public function test_set(): void
    {
        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertEquals([
            'products' => [
                'desk' => [
                    'price' => 200,
                ],
            ],
        ], $array);

        // The key doesn't exist at the depth
        $array = [
            'products' => 'desk',
        ];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertSame([
            'products' => [
                'desk' => [
                    'price' => 200,
                ],
            ],
        ], $array);

        // No corresponding key exists
        $array = ['products'];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertSame([
            'products',
            'products' => [
                'desk' => [
                    'price' => 200,
                ],
            ],
        ], $array);

        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        Arr::set($array, 'table', 500);
        $this->assertSame([
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
            'table' => 500,
        ], $array);

        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        Arr::set($array, 'table.price', 350);
        $this->assertSame(
            [
                'products' => [
                    'desk' => [
                        'price' => 100,
                    ],
                ],
                'table' => [
                    'price' => 350,
                ],
            ],
            $array
        );

        $array = [];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertSame([
            'products' => [
                'desk' => [
                    'price' => 200,
                ],
            ],
        ], $array);

        // Override
        $array = [
            'products' => 'table',
        ];
        Arr::set($array, 'products.desk.price', 300);
        $this->assertSame([
            'products' => [
                'desk' => [
                    'price' => 300,
                ],
            ],
        ], $array);
    }

    /**
     * @test
     */
    public function test_has(): void
    {
        $array = [
            'products.desk' => [
                'price' => 100,
            ],
        ];
        $this->assertTrue(Arr::has($array, 'products.desk'));

        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        $this->assertTrue(Arr::has($array, 'products.desk'));
        $this->assertTrue(Arr::has($array, 'products.desk.price'));
        $this->assertFalse(Arr::has($array, 'products.foo'));
        $this->assertFalse(Arr::has($array, 'products.desk.foo'));

        $array = [
            'foo' => null,
            'bar' => [
                'baz' => null,
            ],
        ];
        $this->assertTrue(Arr::has($array, 'foo'));
        $this->assertTrue(Arr::has($array, 'bar.baz'));

        $array = new ArrayObject([
            'foo' => 10,
            'bar' => new ArrayObject([
                'baz' => 10,
            ]),
        ]);
        $this->assertTrue(Arr::has($array, 'foo'));
        $this->assertTrue(Arr::has($array, 'bar'));
        $this->assertTrue(Arr::has($array, 'bar.baz'));
        $this->assertFalse(Arr::has($array, 'xxx'));
        $this->assertFalse(Arr::has($array, 'xxx.yyy'));
        $this->assertFalse(Arr::has($array, 'foo.xxx'));
        $this->assertFalse(Arr::has($array, 'bar.xxx'));

        $array = new ArrayObject([
            'foo' => null,
            'bar' => new ArrayObject([
                'baz' => null,
            ]),
        ]);
        $this->assertTrue(Arr::has($array, 'foo'));
        $this->assertTrue(Arr::has($array, 'bar.baz'));

        $array = [
            'products' => [
                [
                    'name' => 'desk',
                ],
            ],
        ];
        $this->assertTrue(Arr::has($array, 'products.0.name'));
        $this->assertFalse(Arr::has($array, 'products.0.price'));

        $this->assertFalse(Arr::has([''], ''));

        $this->assertTrue(Arr::has([
            '' => 'some',
        ], ''));

        $this->assertFalse(Arr::has([], ''));
    }

    /**
     * @test
     */
    public function test_has_all(): void
    {
        $array = [
            'products' => [
                'desk' => [
                    'price' => 100,
                ],
            ],
        ];
        $this->assertTrue(Arr::hasAll($array, ['products.desk']));
        $this->assertTrue(Arr::hasAll($array, ['products.desk', 'products.desk.price']));
        $this->assertTrue(Arr::hasAll($array, ['products', 'products']));
        $this->assertFalse(Arr::hasAll($array, []));
        $this->assertFalse(Arr::hasAll($array, ['foo']));
        $this->assertFalse(Arr::hasAll($array, ['products.desk', 'products.price']));
        $this->assertFalse(Arr::hasAll([
            'foo' => 'bar',
        ], []));

        $this->assertFalse(Arr::hasAll([], ['foo']));
    }

    /**
     * @test
     */
    public function test_has_any(): void
    {
        $array = [
            'name' => 'calvin',
            'age' => '',
            'city' => null,
        ];
        $this->assertTrue(Arr::hasAny($array, ['name']));
        $this->assertTrue(Arr::hasAny($array, ['age']));
        $this->assertTrue(Arr::hasAny($array, ['city']));
        $this->assertFalse(Arr::hasAny($array, ['foo']));
        $this->assertTrue(Arr::hasAny($array, ['name', 'email']));
        $this->assertTrue(Arr::hasAny($array, ['name', 'email']));

        $array = [
            'name' => 'calvin',
            'email' => 'foo',
        ];
        $this->assertTrue(Arr::hasAny($array, ['name', 'email']));
        $this->assertFalse(Arr::hasAny($array, ['surname', 'password']));
        $this->assertFalse(Arr::hasAny($array, ['surname', 'password']));

        $array = [
            'foo' => [
                'bar' => null,
                'baz' => '',
            ],
        ];
        $this->assertTrue(Arr::hasAny($array, ['foo.bar']));
        $this->assertTrue(Arr::hasAny($array, ['foo.baz']));
        $this->assertFalse(Arr::hasAny($array, ['foo.bax']));
        $this->assertTrue(Arr::hasAny($array, ['foo.bax', 'foo.baz']));

        $this->assertFalse(Arr::hasAny([], ['foo', 'bar']));
        $this->assertFalse(Arr::hasAny([
            'foo' => 'bar',
        ], []));
    }

    /**
     * @test
     */
    public function test_merge_recursive(): void
    {
        $this->assertEquals([
            'foo' => 'baz',
        ], Arr::mergeRecursive([
            'foo' => 'bar',
        ], [
            'foo' => 'baz',
        ]));

        $this->assertEquals(
            [
                'foo' => 'bar',
                'biz' => 'boo',
            ],
            Arr::mergeRecursive([
                'foo' => 'bar',
            ], [
                'biz' => 'boo',
            ])
        );

        $this->assertEquals(
            [
                'foo' => [
                    'bar' => 'baz',
                    'boo' => 'biz',
                ],
                'bar' => [
                    'bar' => 'baz',
                    'boo' => 'biz',
                ],
            ],
            Arr::mergeRecursive(
                [
                    'foo' => [
                        'bar' => 'baz',
                        'boo' => 'biz',
                    ],
                ],
                [
                    'bar' => [
                        'bar' => 'baz',
                        'boo' => 'biz',
                    ],
                ]
            )
        );

        $this->assertEquals(
            [
                'foo' => [
                    'bar' => ['biz'],
                    'boo' => 'baz',
                    'foo' => 'bar',
                    'bang' => 'boom',
                ],
            ],
            Arr::mergeRecursive(
                [
                    'foo' => [
                        'bar' => 'baz',
                        'boo' => 'biz',
                        'bang' => 'boom',
                    ],
                ],
                [
                    'foo' => [
                        'bar' => ['biz'],
                        'boo' => 'baz',
                        'foo' => 'bar',
                    ],
                ]
            )
        );
    }

    /**
     * @test
     */
    public function test_is_assoc(): void
    {
        $this->assertTrue(Arr::isAssoc([
            'foo' => 'bar',
            'bar' => 'baz',
        ]));
        $this->assertTrue(Arr::isAssoc([]));
        $this->assertFalse(Arr::isAssoc([
            'foo' => 'bar',
            'bar' => 'baz',
            'baz',
        ]));
        $this->assertFalse(Arr::isAssoc(['foo', 'bar']));
    }

    /**
     * @test
     */
    public function test_is_list(): void
    {
        $this->assertTrue(Arr::isList([]));
        $this->assertTrue(Arr::isList(['foo', 'bar']));
        $this->assertFalse(Arr::isList([
            'foo' => 'bar',
            'bar' => 'baz',
        ]));
        $this->assertFalse(Arr::isList([
            'foo',
            'bar',
            'baz' => 'biz',
        ]));
    }
}

final class SupportTestArrayAccess implements ArrayAccess
{
    private array $attributes;

    /**
     * @param mixed[] $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @param int|string $offset
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->attributes);
    }

    /**
     * @param int|string $offset
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    /**
     * @param int|string $offset
     * @param mixed      $value
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * @param int|string $offset
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }
}
