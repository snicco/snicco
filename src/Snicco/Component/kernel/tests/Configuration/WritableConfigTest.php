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

        $this->config = WritableConfig::fromArray([]);
    }

    /**
     * @test
     */
    public function that_set_if_missing_works(): void
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
    public function that_a_value_can_be_a_boolean_false(): void
    {
        $this->config->set('foo', false);

        $this->assertFalse($this->config->get('foo'));
    }

    /**
     * @test
     */
    public function that_a_value_can_be_null(): void
    {
        $this->config->set('foo', null);

        $this->assertNull($this->config->get('foo'));
    }

    /**
     * @test
     */
    public function that_a_value_can_be_string_int_zero(): void
    {
        $this->config->set('foo', '0');
        $this->config->set('bar', 0);

        $this->assertSame('0', $this->config->get('foo'));
        $this->assertSame(0, $this->config->get('bar'));
    }

    /**
     * @test
     */
    public function that_default_configuration_can_be_merged_recursively(): void
    {
        $config = [
            'routing' => [
                'definitions' => ['routes1', 'routes2'],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];
        $config = WritableConfig::fromArray($config);

        $config->mergeDefaults(
            'routing',
            [
                'definitions' => ['routes3', 'routes4'],
                'features' => [
                    'feature1' => false,
                    'feature2' => true,
                    'feature3' => true,
                    'feature4' => 'yes',
                ],
                'dir' => __DIR__,
            ]
        );

        $this->assertSame([
            'definitions' => ['routes1', 'routes2'],
            'features' => [
                'feature1' => true,
                'feature2' => false,
            ],
            'dir' => __DIR__,
        ], $config->get('routing'));
    }

    /**
     * @test
     */
    public function that_merging_default_configuration_on_a_non_array_value_throws_an_exception(): void
    {
        $config = [
            'routing' => [
                'foo' => 'bar',
            ],
        ];
        $config = WritableConfig::fromArray($config);

        $this->expectException(InvalidArgumentException::class);
        $config->mergeDefaults('routing.foo', []);
    }

    /**
     * @test
     */
    public function that_merging_default_configuration_works_with_empty_current_config(): void
    {
        $config = [];
        $config = WritableConfig::fromArray($config);
        $config->mergeDefaults('routing', [
            'foo' => 'bar',
        ]);
        $this->assertSame([
            'foo' => 'bar',
        ], $config->get('routing'));
    }

    /**
     * @test
     */
    public function that_defaults_can_be_merged_from_a_file(): void
    {
        $config = WritableConfig::fromArray([]);

        $config->mergeDefaultsFromFile(__DIR__ . '/fixtures/routing.php');

        $this->assertSame([
            'foo' => 'bar',
        ], $config->get('routing'));
    }

    /**
     * @test
     */
    public function that_values_can_be_appended_to_a_list(): void
    {
        $config = [
            'routing' => [
                'definitions' => ['routes1', 'routes2'],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $config->appendToList('routing.definitions', 'routes3');

        $this->assertSame(['routes1', 'routes2', 'routes3'], $config->get('routing.definitions'));

        $config->appendToList('routing.definitions', ['routes4', 'routes5']);

        $this->assertSame([
            'routes1',
            'routes2',
            'routes3',
            'routes4',
            'routes5',
        ], $config->get('routing.definitions'));

        $config->appendToList('routing.definitions', 'routes5');

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

        $config->appendToList('routing.features', 'foo');
    }

    /**
     * @test
     */
    public function test_append_throws_for_different_scalar_type(): void
    {
        $config = [
            'routing' => [
                'definitions' => ['routes1', 'routes2'],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected scalar type [string].\nGot [integer].");
        $config->appendToList('routing.definitions', 1);
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

        $config->appendToList('routing.definitions', ['routes1', 'routes2']);

        $this->assertSame(['routes1', 'routes2'], $config->get('routing.definitions'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('boolean');
        $config->appendToList('routing.definitions', ['routes3', true]);
    }

    /**
     * @test
     */
    public function test_append_throws_for_missing_key(): void
    {
        $config = [
            'routing' => [],
        ];

        $config = WritableConfig::fromArray($config);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('missing config key [routing.definitions]');
        $config->appendToList('routing.definitions', 'foo');
    }

    /**
     * @test
     */
    public function test_prepend(): void
    {
        $config = [
            'routing' => [
                'definitions' => ['routes1', 'routes2'],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $config->prependToList('routing.definitions', 'routes3');

        $this->assertSame(['routes3', 'routes1', 'routes2'], $config->get('routing.definitions'));

        $config->prependToList('routing.definitions', ['routes4', 'routes5']);

        $this->assertSame([
            'routes5',
            'routes4',
            'routes3',
            'routes1',
            'routes2',
        ], $config->get('routing.definitions'));

        $config->prependToList('routing.definitions', 'routes5');

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

        $config->prependToList('routing.features', 'foo');
    }

    /**
     * @test
     */
    public function test_prepend_throws_for_different_scalar_type(): void
    {
        $config = [
            'routing' => [
                'definitions' => ['routes1', 'routes2'],
                'features' => [
                    'feature1' => true,
                    'feature2' => false,
                ],
            ],
        ];

        $config = WritableConfig::fromArray($config);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected scalar type [string].\nGot [integer].");
        $config->prependToList('routing.definitions', 1);
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

        $config->prependToList('routing.definitions', ['routes1', 'routes2']);

        $this->assertSame(['routes2', 'routes1'], $config->get('routing.definitions'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('boolean');
        $config->prependToList('routing.definitions', ['routes3', true]);
    }

    /**
     * @test
     */
    public function test_prepend_throws_for_missing_key(): void
    {
        $config = [
            'routing' => [],
        ];

        $config = WritableConfig::fromArray($config);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('missing config key [routing.definitions]');
        $config->prependToList('routing.definitions', 'foo');
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
    public function test_get_boolean_or_null(): void
    {
        $config = WritableConfig::fromArray([
            'foo' => true,
            'baz' => 1,
        ]);

        $this->assertTrue($config->getBooleanOrNull('foo'));
        $this->assertFalse($config->getBooleanOrNull('bogus', false));
        $this->assertNull($config->getBooleanOrNull('bogus'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a boolean or null for config key [baz]');

        $config->getBooleanOrNull('baz');
    }

    /**
     * @test
     */
    public function test_get_string_or_null(): void
    {
        $config = WritableConfig::fromArray([
            'foo' => 'bar',
            'baz' => 1,
        ]);

        $this->assertSame('bar', $config->getStringOrNull('foo'));
        $this->assertSame('default', $config->getStringOrNull('bogus', 'default'));
        $this->assertNull($config->getStringOrNull('non-existing'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string or null for config key [baz]');

        $config->getStringOrNull('baz');
    }

    /**
     * @test
     */
    public function test_get_int_or_null(): void
    {
        $config = WritableConfig::fromArray([
            'foo' => 'bar',
            'baz' => 1,
        ]);

        $this->assertSame(1, $config->getIntegerOrNull('baz'));
        $this->assertSame(2, $config->getIntegerOrNull('bogus', 2));
        $this->assertNull($config->getIntegerOrNull('bogus'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an integer or null for config key [foo]');

        $config->getIntegerOrNull('foo');
    }

    /**
     * @test
     */
    public function test_get_list_of_string(): void
    {
        $config = WritableConfig::fromArray([
            'routes' => ['route1', 'route2', 'route3'],
            'bad_routes' => ['route1', 'route2', 1],
            'associative_routes' => [
                'routes' => [
                    'foo' => 'route1',
                    'route2',
                    'route3',
                ],
            ],
        ]);

        $res = $config->getListOfStrings('routes');

        $this->assertSame(['route1', 'route2', 'route3'], $res);

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
            'routes' => ['route1', 'route2', 'route3'],
            'bad_routes' => ['route1', 'route2', 1],
            'associative_routes' => [
                'foo' => 'route1',
                'route2',
                'route3',
            ],
            'foo' => 'string',
        ]);

        $res = $config->getArray('routes');

        $this->assertSame(['route1', 'route2', 'route3'], $res);

        $this->assertSame(['route1', 'route2', 1], $config->getArray('bad_routes'));

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
