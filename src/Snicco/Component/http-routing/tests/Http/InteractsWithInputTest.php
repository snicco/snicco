<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Testing\CreatesPsrRequests;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

/**
 * @internal
 */
final class InteractsWithInputTest extends TestCase
{
    use CreateTestPsr17Factories;
    use CreatesPsrRequests;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->frontendRequest('/foo');
    }

    /**
     * @test
     */
    public function test_server(): void
    {
        $request = $this->frontendRequest('/foo', [
            'foo' => 'bar',
        ]);

        $this->assertSame('bar', $request->server('foo'));

        $this->assertSame('default', $request->server('bogus', 'default'));
    }

    /**
     * @test
     */
    public function test_all(): void
    {
        $request = $this->request->withQueryParams([
            'foo' => 'bar',
        ]);
        $this->assertSame([
            'foo' => 'bar',
        ], $request->all());

        $request = $this->frontendRequest('/foo', [], 'POST')->withParsedBody([
            'foo' => 'bar',
        ]);
        $this->assertSame([
            'foo' => 'bar',
        ], $request->all());

        $request = $this->request->withQueryParams([
            'foo' => 'bar',
        ])->withParsedBody([
            'baz' => 'biz',
        ]);
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'biz',
        ], $request->all());
    }

    /**
     * @test
     */
    public function the_http_verb_is_taken_into_account_for_all(): void
    {
        $request = $this->request->withMethod('POST')
            ->withQueryParams([
                'foo' => 'bar',
                'baz' => 'biz',
            ])
            ->withParsedBody([
                'boo' => 'bam',
                'foo' => 'foobar',
            ]);

        $this->assertSame([
            'boo' => 'bam',
            'foo' => 'foobar',
            'baz' => 'biz',
        ], $request->all());

        $request = $request->withMethod('GET');
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'biz',
            'boo' => 'bam',
        ], $request->all());
    }

    /**
     * @test
     */
    public function test_input_is_alias_for_all(): void
    {
        $request = $this->request->withQueryParams(
            [
                'foo' => 'bar',
                'baz' => 'biz',
                'team' => [
                    'player' => 'calvin',
                ],
            ]
        );

        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'biz',
            'team' => [
                'player' => 'calvin',
            ],
        ], $request->all());
    }

    /**
     * @test
     */
    public function test_query(): void
    {
        $request = $this->request->withQueryParams([
            'foo' => 'bar',
        ]);
        $this->assertSame([
            'foo' => 'bar',
        ], $request->query());
        $this->assertSame('bar', $request->query('foo'));
        $this->assertSame('default', $request->query('bogus', 'default'));

        $request = $this->frontendRequest('/foo', [], 'POST')->withParsedBody([
            'foo' => 'bar',
        ]);
        $this->assertNull($request->query('foo'));

        $request = $this->request->withQueryParams(
            [
                'foo' => 'bar',
                'baz' => 'biz',
                'team' => [
                    'player' => 'calvin',
                ],
            ]
        );
        $this->assertSame('bar', $request->query('foo'));
        $this->assertSame('default', $request->query('bogus', 'default'));
        $this->assertSame('calvin', $request->query('team.player'));

        $request = $this->request->withQueryParams([
            'products' => [
                [
                    'name' => 'shoe',
                    'price' => '10',
                ],
                [
                    'name' => 'shirt',
                    'price' => '25',
                ],
            ],
        ]);

        $name = $request->query('products.0.name');
        $this->assertSame('shoe', $name);
    }

    /**
     * @test
     */
    public function test_post(): void
    {
        $request = $this->request->withParsedBody([
            'foo' => 'bar',
        ]);
        $this->assertSame([
            'foo' => 'bar',
        ], $request->post());
        $this->assertSame('bar', $request->post('foo'));
        $this->assertSame('default', $request->post('bogus', 'default'));

        $request = $this->frontendRequest('/foo', [], 'POST')->withQueryParams([
            'foo' => 'bar',
        ]);
        $this->assertNull($request->post('foo'));

        $request = $this->request->withParsedBody(
            [
                'foo' => 'bar',
                'baz' => 'biz',
                'team' => [
                    'player' => 'calvin',
                ],
            ]
        );
        $this->assertSame('bar', $request->post('foo'));
        $this->assertSame('default', $request->post('bogus', 'default'));
        $this->assertSame('calvin', $request->post('team.player'));

        $request = $this->request->withParsedBody([
            'products' => [
                [
                    'name' => 'shoe',
                    'price' => '10',
                ],
                [
                    'name' => 'shirt',
                    'price' => '25',
                ],
            ],
        ]);

        $name = $request->post('products.0.name');
        $this->assertSame('shoe', $name);
    }

    /**
     * @test
     */
    public function test_post_throws_exception_if_parsed_body_is_not_an_array(): void
    {
        $arr = [
            'foo' => 'bar',
        ];
        $stdClass = (object) $arr;
        $request = $this->request->withParsedBody($stdClass);

        $this->assertSame($stdClass, $request->getParsedBody());

        $this->expectException(RuntimeException::class);
        $request->post('foo');
    }

    /**
     * @test
     */
    public function test_post_returns_default_if_parsed_body_is_null(): void
    {
        $request = $this->request->withParsedBody(null);
        $this->assertSame('default', $request->post('foo', 'default'));
    }

    /**
     * @test
     */
    public function test_query_string(): void
    {
        $request = $this->frontendRequest('https://foobar.com?foo=bar&baz=biz&=');

        $this->assertSame('foo=bar&baz=biz', $request->queryString());
    }

    /**
     * @test
     */
    public function test_boolean_when_method_is_get(): void
    {
        $request = $this->request->withQueryParams([
            'foo' => 1,
            'bar' => '1',
            'baz' => 'on',
            'biz' => 'yes',
            'boo' => true,
            'bam' => 'true',
            'bogus' => 'bogus',
        ])->withParsedBody([
            'post' => true,
        ]);

        $this->assertTrue($request->boolean('foo'));
        $this->assertTrue($request->boolean('bar'));
        $this->assertTrue($request->boolean('baz'));
        $this->assertTrue($request->boolean('biz'));
        $this->assertTrue($request->boolean('boo'));
        $this->assertTrue($request->boolean('bam'));
        $this->assertFalse($request->boolean('bogus'));

        $this->assertFalse($request->boolean('post'));
    }

    /**
     * @test
     */
    public function test_boolean_when_method_is_not_get(): void
    {
        $request = $this->frontendRequest('/', [], 'POST')->withParsedBody([
            'foo' => 1,
            'bar' => '1',
            'baz' => 'on',
            'biz' => 'yes',
            'boo' => true,
            'bam' => 'true',
            'bogus' => 'bogus',
        ])->withQueryParams([
            'get' => true,
        ]);

        $this->assertTrue($request->boolean('foo'));
        $this->assertTrue($request->boolean('bar'));
        $this->assertTrue($request->boolean('baz'));
        $this->assertTrue($request->boolean('biz'));
        $this->assertTrue($request->boolean('boo'));
        $this->assertTrue($request->boolean('bam'));
        $this->assertFalse($request->boolean('bogus'));

        $this->assertFalse($request->boolean('get'));
    }

    /**
     * @test
     */
    public function test_only_when_method_is_get(): void
    {
        $request = $this->request->withQueryParams([
            'product' => [
                'name' => 'shoe',
                'price' => '10',
            ],
        ])->withParsedBody([
            'foo' => [
                'bar' => 'baz',
            ],
        ]);

        $only = $request->only('product.name');
        $expected = [
            'product' => [
                'name' => 'shoe',
            ],
        ];
        $this->assertSame($expected, $only);

        $only = $request->only('product.description');
        $expected = [];
        $this->assertSame($expected, $only);

        $this->assertSame([], $request->only(['foo.bar']));
    }

    /**
     * @test
     */
    public function test_only_when_method_is_not_get_or_head(): void
    {
        $request = $this->frontendRequest('/', [], 'POST');
        $request = $request->withQueryParams([
            'product' => [
                'name' => 'shoe',
                'price' => '10',
            ],
        ])->withParsedBody([
            'foo' => [
                'bar' => 'baz',
                'baz' => 'biz',
            ],
        ]);

        $this->assertSame([], $request->only('product.name'));
        $this->assertSame([
            'foo' => [
                'bar' => 'baz',
                'baz' => 'biz',
            ],
        ], $request->only(['foo']));
        $this->assertSame([
            'foo' => [
                'bar' => 'baz',
            ],
        ], $request->only('foo.bar'));
    }

    /**
     * @test
     */
    public function test_except_when_method_is_get(): void
    {
        $request = $this->request->withQueryParams([
            'product' => [
                'name' => 'shoe',
                'price' => '10',
            ],
            'merchant' => [
                'name' => 'calvin',
                'age' => '23',
            ],
        ])->withParsedBody([
            'foo' => 'bar',
            'baz' => 'biz',
        ]);

        $input = $request->except(['product.name', 'merchant']);

        // foo is not included.
        $expected = [
            'product' => [
                'price' => '10',
            ],
        ];

        $this->assertSame($expected, $input);
    }

    /**
     * @test
     */
    public function test_except_when_method_is_post(): void
    {
        $request = $this->frontendRequest('/', [], 'POST')->withQueryParams([
            'product' => [
                'name' => 'shoe',
                'price' => '10',
            ],
            'merchant' => [
                'name' => 'calvin',
                'age' => '23',
            ],
        ])->withParsedBody([
            'foo' => 'bar',
            'baz' => 'biz',
        ]);

        $input = $request->except(['product.name', 'merchant']);

        // Post data is not taken into account
        $expected = [
            'foo' => 'bar',
            'baz' => 'biz',
        ];
        $this->assertSame($expected, $input);

        $expected = [
            'foo' => 'bar',
        ];
        $this->assertSame($expected, $request->except(['baz']));
    }

    /**
     * @test
     */
    public function test_has(): void
    {
        $request = $this->request->withQueryParams([
            'products' => [
                [
                    'name' => 'shoe',
                    'price' => '10',
                ],
                [
                    'name' => 'shirt',
                    'price' => '25',
                ],
            ],
            'dev' => 'calvin',
            'null' => null,
            'empty_string' => '',
        ])->withParsedBody([
            'foo' => 'bar',
        ]);

        $this->assertTrue($request->has('products'));
        $this->assertTrue($request->has('products.0.name'));
        $this->assertTrue($request->has('products.0.price'));

        $this->assertTrue($request->has('null'));
        $this->assertTrue($request->has('empty_string'));

        $this->assertFalse($request->has('foo'));
    }

    /**
     * @test
     */
    public function test_has_all(): void
    {
        $request = $this->request->withQueryParams([
            'products' => [
                [
                    'name' => 'shoe',
                    'price' => '10',
                ],
                [
                    'name' => 'shirt',
                    'price' => '25',
                ],
            ],
            'dev' => 'calvin',
            'null' => null,
            'empty_string' => '',
        ]);

        $this->assertTrue($request->hasAll(['products.0.name', 'products.0.price']));
        $this->assertTrue($request->hasAll(['products.0.name', 'products.0.price', 'dev']));
        $this->assertFalse($request->hasAll(['products.0.name', 'products.0.price', 'products.0.label']));
    }

    /**
     * @test
     */
    public function test_has_any(): void
    {
        $request = $this->request->withQueryParams([
            'name' => 'calvin',
            'age' => '',
            'city' => null,
        ]);
        $this->assertTrue($request->hasAny(['name']));
        $this->assertTrue($request->hasAny(['age']));
        $this->assertTrue($request->hasAny(['city']));
        $this->assertTrue($request->hasAny(['name', 'email']));
        $this->assertFalse($request->hasAny(['foo']));

        $request = $this->request->withQueryParams([
            'name' => 'calvin',
            'email' => 'foo',
        ]);
        $this->assertTrue($request->hasAny(['name', 'email']));
        $this->assertFalse($request->hasAny(['surname', 'password']));

        $request = $this->request->withQueryParams([
            'foo' => [
                'bar' => null,
                'baz' => '',
            ],
        ]);
        $this->assertTrue($request->hasAny(['foo.bar']));
        $this->assertTrue($request->hasAny(['foo.baz']));
        $this->assertFalse($request->hasAny(['foo.bax']));
        $this->assertTrue($request->hasAny(['foo.bax', 'foo.baz']));

        $request = $this->request->withQueryParams([
            'foo' => 'bar',
            'baz' => 'biz',
        ])->withParsedBody([
            'boom' => 'bam',
        ]);
        $this->assertTrue($request->hasAny(['foo']));
        $this->assertTrue($request->hasAny(['baz']));
        $this->assertFalse($request->hasAny(['boom']));
        $this->assertTrue($request->hasAny(['baz', 'bogus']));
    }

    /**
     * @test
     */
    public function test_filled(): void
    {
        $request = $this->request->withQueryParams([
            'dev' => 'calvin',
            'foo' => '',
            'null' => null,
            'array' => [],
            'empty-string' => '                ',
        ])->withParsedBody([
            'boom' => 'bam',
        ]);

        $this->assertTrue($request->filled('dev'));
        $this->assertFalse($request->filled('foo'));
        $this->assertFalse($request->filled('null'));
        $this->assertFalse($request->filled('array'));
        $this->assertFalse($request->filled('empty-string'));

        // only query parameters are considered for get.
        $this->assertFalse($request->filled('boom'));
    }

    /**
     * @test
     */
    public function test_missing(): void
    {
        $request = $this->request->withQueryParams([
            'name' => 'calvin',
            'age' => '',
            'city' => null,
        ]);
        $this->assertFalse($request->missing('name'));
        $this->assertFalse($request->missing('age'));
        $this->assertFalse($request->missing('city'));
        $this->assertTrue($request->missing('foo'));
        $this->assertTrue($request->missing('email'));

        $request = $this->request->withQueryParams([
            'foo' => [
                'bar' => null,
                'baz' => '',
            ],
        ])->withParsedBody([
            'boom' => 'bam',
        ]);
        $this->assertFalse($request->missing('foo.bar'));
        $this->assertFalse($request->missing('foo.baz'));
        $this->assertTrue($request->missing('foo.bax'));
        $this->assertTrue($request->missing('boom'));
    }
}
