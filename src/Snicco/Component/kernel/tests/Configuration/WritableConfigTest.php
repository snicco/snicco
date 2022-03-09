<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\Configuration;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Kernel\Configuration\WritableConfig;

/**
 * @internal
 */
final class WritableConfigTest extends TestCase
{
    private WritableConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new WritableConfig();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->config = null;
    }

    /**
     * @test
     */
    public function test_set_if_missing(): void
    {
        $this->config->set('foo', null);
        $this->config->setIfMissing('foo', 'bar');
        $this->assertNull($this->config->get('foo'));

        $this->config->setIfMissing('baz', 'biz');
        $this->assertSame('biz', $this->config->get('baz'));
    }

    /**
     * @test
     */
    public function a_value_can_be_a_boolean_false(): void
    {
        $this->config->extend('foo', false);

        $this->assertFalse($this->config->get('foo'));
    }

    /**
     * @test
     */
    public function a_value_can_be_null(): void
    {
        $this->config->extend('foo', null);

        $this->assertNull($this->config->get('foo'));
    }

    /**
     * @test
     */
    public function a_value_can_be_string_int_zero(): void
    {
        $this->config->extend('foo', '0');
        $this->config->extend('bar', 0);

        $this->assertSame('0', $this->config->get('foo'));
        $this->assertSame(0, $this->config->get('bar'));
    }

    /**
     * @test
     */
    public function the_default_gets_set_if_the_key_is_not_present_in_the_user_config(): void
    {
        $this->assertNull($this->config->get('foo'));

        $this->config->extend('foo', 'bar');

        $this->assertEquals('bar', $this->config->get('foo'));
    }

    /**
     * @test
     */
    public function user_config_has_precedence_over_default_config(): void
    {
        $this->assertNull($this->config->get('foo'));

        $this->config->set('foo', 'bar');

        $this->assertSame('bar', $this->config->get('foo'));

        $this->config->extend('foo', 'baz');

        $this->assertSame('bar', $this->config->get('foo'));
    }

    /**
     * @test
     */
    public function user_config_has_precedence_over_default_config_and_gets_merged_recursively(): void
    {
        $config = [
            'routing' => [
                'definitions' => [
                    'routes1',
                    'routes2',
                ],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $config->extend('routing.definitions', [
            'routes3',
            'routes4',
        ]);

        $config->extend('routing.features', [
            'feature1' => false,
            'feature2' => true,
            'feature3' => true,
            'feature4' => 'yes',
        ]);

        $this->assertSame([
            'routes1',
            'routes2',
            'routes3',
            'routes4',
        ], $config->get('routing.definitions'));

        $this->assertSame([
            'feature1' => true,
            'feature2' => false,
            'feature3' => true,
            'feature4' => 'yes',
        ], $config->get('routing.features'));
    }

    /**
     * @test
     */
    public function everything_works_with_dot_notation_as_well(): void
    {
        $config = WritableConfig::fromArray([
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

        $config->extend('foo.baz', [
            'bam' => 'boom',
        ]);
        $this->assertEquals([
            'biz' => 'boo',
            'bam' => 'boom',
        ], $config->get('foo.baz'));

        $config->extend('foo.baz.biz', 'bogus');
        $this->assertEquals('boo', $config->get('foo.baz.biz'));
    }

    /**
     * @test
     */
    public function numerically_indexed_arrays_are_merged_and_unique_values_remain(): void
    {
        $config = WritableConfig::fromArray([
            'first' => [
                'foo',
                'bar',
            ],
        ]);

        $config->extend('first', ['boo', 'bar', 'biz', 'foo']);

        $this->assertEquals(['foo', 'bar', 'boo', 'biz'], $config->get('first'));
    }

    /**
     * @test
     */
    public function scalar_values_can_be_pushed_onto_an_array(): void
    {
        $config = WritableConfig::fromArray([
            'foo' => ['bar'],
        ]);

        $config->extend('foo', 'baz');

        $this->assertSame(['bar', 'baz'], $config->get('foo'));
    }

    /**
     * @test
     */
    public function test_append(): void
    {
        $config = [
            'routing' => [
                'definitions' => [
                    'routes1',
                    'routes2',
                ],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $config->append('routing.definitions', 'routes3');

        $this->assertSame([
            'routes1',
            'routes2',
            'routes3',
        ], $config->get('routing.definitions'));

        $config->append('routing.definitions', ['routes4', 'routes5']);

        $this->assertSame([
            'routes1',
            'routes2',
            'routes3',
            'routes4',
            'routes5',
        ], $config->get('routing.definitions'));

        $config->append('routing.definitions', 'routes5');

        // Unique values only
        $this->assertSame([
            'routes1',
            'routes2',
            'routes3',
            'routes4',
            'routes5',
        ], $config->get('routing.definitions'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cant append to key [routing.features] because its not a list.');

        $config->append('routing.features', 'foo');
    }

    /**
     * @test
     */
    public function test_append_throws_for_different_scalar_type(): void
    {
        $config = [
            'routing' => [
                'definitions' => [
                    'routes1',
                    'routes2',
                ],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected scalar type [string].\nGot [integer].");
        $config->append('routing.definitions', 1);
    }

    /**
     * @test
     */
    public function test_append_works_for_empty_array(): void
    {
        $config = [
            'routing' => [
                'definitions' => [],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $config->append('routing.definitions', ['routes1', 'routes2']);

        $this->assertSame(['routes1', 'routes2'], $config->get('routing.definitions'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('boolean');
        $config->append('routing.definitions', ['routes3', true]);
    }

    /**
     * @test
     */
    public function test_append_throws_for_missing_key(): void
    {
        $config = [
            'routing' => [
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('missing config key [routing.definitions]');
        $config->append('routing.definitions', 'foo');
    }

    /**
     * @test
     */
    public function test_prepend(): void
    {
        $config = [
            'routing' => [
                'definitions' => [
                    'routes1',
                    'routes2',
                ],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $config->prepend('routing.definitions', 'routes3');

        $this->assertSame([
            'routes3',
            'routes1',
            'routes2',
        ], $config->get('routing.definitions'));

        $config->prepend('routing.definitions', ['routes4', 'routes5']);

        $this->assertSame([
            'routes5',
            'routes4',
            'routes3',
            'routes1',
            'routes2',
        ], $config->get('routing.definitions'));

        $config->prepend('routing.definitions', 'routes5');

        // Unique values only
        $this->assertSame([
            'routes5',
            'routes4',
            'routes3',
            'routes1',
            'routes2',
        ], $config->get('routing.definitions'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cant prepend to key [routing.features] because its not a list.');

        $config->prepend('routing.features', 'foo');
    }

    /**
     * @test
     */
    public function test_prepend_throws_for_different_scalar_type(): void
    {
        $config = [
            'routing' => [
                'definitions' => [
                    'routes1',
                    'routes2',
                ],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected scalar type [string].\nGot [integer].");
        $config->prepend('routing.definitions', 1);
    }

    /**
     * @test
     */
    public function test_prepend_works_for_empty_array(): void
    {
        $config = [
            'routing' => [
                'definitions' => [],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $config->prepend('routing.definitions', ['routes1', 'routes2']);

        $this->assertSame(['routes2', 'routes1'], $config->get('routing.definitions'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('boolean');
        $config->prepend('routing.definitions', ['routes3', true]);
    }

    /**
     * @test
     */
    public function test_prepend_throws_for_missing_key(): void
    {
        $config = [
            'routing' => [
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('missing config key [routing.definitions]');
        $config->prepend('routing.definitions', 'foo');
    }

    /**
     * @test
     */
    public function test_merge_if_current_is_empty_array(): void
    {
        $config = [
            'routing' => [],
            'foo' => [],
        ];

        $config = WritableConfig::fromArray($config);
        $config->extend('routing', 'foo');
        $this->assertSame(['foo'], $config->get('routing'));

        $config->extend('foo', ['bar']);
        $this->assertSame(['bar'], $config->get('foo'));
    }

    /**
     * @test
     */
    public function test_get_string(): void
    {
        $config = WritableConfig::fromArray([
            'foo' => 'bar',
            'baz' => 1,
        ]);

        $this->assertSame('bar', $config->getString('foo'));
        $this->assertSame('default', $config->getString('bogus', 'default'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string for config key [baz]');

        $config->getString('baz');
    }

    /**
     * @test
     */
    public function test_get_integer(): void
    {
        $config = WritableConfig::fromArray([
            'foo' => 'bar',
            'baz' => 1,
        ]);

        $this->assertSame(1, $config->getInteger('baz'));
        $this->assertSame(2, $config->getInteger('bogus', 2));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an integer for config key [foo]');

        $config->getInteger('foo');
    }

    /**
     * @test
     */
    public function test_get_boolean(): void
    {
        $config = WritableConfig::fromArray([
            'foo' => true,
            'baz' => 1,
        ]);

        $this->assertTrue($config->getBoolean('foo'));
        $this->assertFalse($config->getBoolean('bogus', false));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a boolean for config key [baz]');

        $config->getBoolean('baz');
    }

    /**
     * @test
     */
    public function test_get_list_of_string(): void
    {
        $config = WritableConfig::fromArray([
            'routes' => [
                'route1',
                'route2',
                'route3',
            ],
            'bad_routes' => [
                'route1',
                'route2',
                1,
            ],
            'associative_routes' => [
                'routes' => [
                    'foo' => 'route1',
                    'route2',
                    'route3',
                ],
            ],
        ]);

        $res = $config->getListOfStrings('routes');

        $this->assertSame([
            'route1',
            'route2',
            'route3',
        ], $res);

        try {
            $config->getListOfStrings('bad_routes');
            $this->fail('No exception thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith(
                'Config value for key [bad_routes] is not a list of strings.',
                $e->getMessage()
            );
        }

        try {
            $config->getListOfStrings('associative_routes');
            $this->fail('No exception thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith(
                'Config value for key [associative_routes] is not a list of strings.',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function test_get_array(): void
    {
        $config = WritableConfig::fromArray([
            'routes' => [
                'route1',
                'route2',
                'route3',
            ],
            'bad_routes' => [
                'route1',
                'route2',
                1,
            ],
            'associative_routes' => [
                'foo' => 'route1',
                'route2',
                'route3',
            ],
            'foo' => 'string',
        ]);

        $res = $config->getArray('routes');

        $this->assertSame([
            'route1',
            'route2',
            'route3',
        ], $res);

        $this->assertSame([
            'route1',
            'route2',
            1,
        ], $config->getArray('bad_routes'));

        $this->assertSame([
            'foo' => 'route1',
            'route2',
            'route3',
        ], $config->getArray('associative_routes'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string');
        $config->getArray('foo');
    }
}
