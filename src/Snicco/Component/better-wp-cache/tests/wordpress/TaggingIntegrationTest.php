<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache\Tests\wordpress;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Codeception\TestCase\WPTestCase;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;
use Snicco\Component\BetterWPCache\CacheFactory;
use stdClass;
use WP_Object_Cache;

use function is_array;
use function method_exists;

/**
 * The test methods in this class are copied from
 * https://github.com/php-cache/integration-tests/blob/master/src/ We can't
 * extend the provided test case because we already need to extend WPTestCase.
 *
 * @see https://github.com/php-cache/integration-tests/issues/117
 *
 * @internal
 */
final class TaggingIntegrationTest extends WPTestCase
{
    /**
     * @var array with functionName => reason
     */
    protected array $skippedTests = [];

    protected ?TaggableCacheItemPoolInterface $cache = null;

    protected function setUp(): void
    {
        parent::setUp();
        global $wp_object_cache;

        if (! $wp_object_cache instanceof WP_Object_Cache) {
            throw new RuntimeException('wp object cache not setup.');
        }

        if (! method_exists($wp_object_cache, 'redis_status')) {
            throw new RuntimeException('wp object cache does not have method redis_status');
        }

        if (false === $wp_object_cache->redis_status()) {
            throw new RuntimeException('Redis not running.');
        }
    }

    public function createCachePool(): TaggableCacheItemPoolInterface
    {
        return CacheFactory::taggable(CacheFactory::psr6('testing'));
    }

    /**
     * @before
     */
    public function setupService(): void
    {
        $this->cache = $this->createCachePool();
    }

    /**
     * @after
     */
    public function tearDownService(): void
    {
        if (null !== $this->cache) {
            $this->cache->clear();
        }
    }

    public function invalidKeys(): array
    {
        return [
            [true],
            [false],
            [null],
            [2],
            [2.5],
            ['{str'],
            ['rand{'],
            ['rand{str'],
            ['rand}str'],
            ['rand(str'],
            ['rand)str'],
            ['rand/str'],
            ['rand\\str'],
            ['rand@str'],
            ['rand:str'],
            [new stdClass()],
            [['array']],
        ];
    }

    /**
     * @test
     */
    public function multiple_tags(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->save($this->cache->getItem('key1')->set('value')->setTags(['tag1', 'tag2']));
        $this->cache->save($this->cache->getItem('key2')->set('value')->setTags(['tag1', 'tag3']));
        $this->cache->save($this->cache->getItem('key3')->set('value')->setTags(['tag2', 'tag3']));
        $this->cache->save($this->cache->getItem('key4')->set('value')->setTags(['tag4', 'tag3']));

        $this->cache->invalidateTags(['tag1']);
        $this->assertFalse($this->cache->hasItem('key1'));
        $this->assertFalse($this->cache->hasItem('key2'));
        $this->assertTrue($this->cache->hasItem('key3'));
        $this->assertTrue($this->cache->hasItem('key4'));

        $this->cache->invalidateTags(['tag2']);
        $this->assertFalse($this->cache->hasItem('key1'));
        $this->assertFalse($this->cache->hasItem('key2'));
        $this->assertFalse($this->cache->hasItem('key3'));
        $this->assertTrue($this->cache->hasItem('key4'));
    }

    /**
     * @test
     */
    public function previous_tag(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key')
            ->set('value');
        $tags = $item->getPreviousTags();
        $this->assertTrue(is_array($tags));
        $this->assertCount(0, $tags);

        $item->setTags(['tag0']);
        $this->assertCount(0, $item->getPreviousTags());

        $this->cache->save($item);
        $this->assertCount(0, $item->getPreviousTags());

        $item = $this->cache->getItem('key');
        $this->assertCount(1, $item->getPreviousTags());
    }

    /**
     * @test
     */
    public function previous_tag_deferred(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key')
            ->set('value');
        $item->setTags(['tag0']);
        $this->assertCount(0, $item->getPreviousTags());

        $this->cache->saveDeferred($item);
        $this->assertCount(0, $item->getPreviousTags());

        $item = $this->cache->getItem('key');
        $this->assertCount(1, $item->getPreviousTags());
    }

    /**
     * @test
     */
    public function tag_accessor_with_empty_tag(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key')
            ->set('value');
        $this->expectException(InvalidArgumentException::class);
        $item->setTags(['']);
    }

    /**
     * @dataProvider invalidKeys
     *
     * @param mixed $tag
     *
     * @test
     */
    public function tag_accessor_with_invalid_tag($tag): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key')
            ->set('value');
        $this->expectException(InvalidArgumentException::class);
        $item->setTags([$tag]);
    }

    /**
     * @test
     */
    public function tag_accessor_duplicate_tags(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key')
            ->set('value');
        $item->setTags(['tag', 'tag', 'tag']);

        $this->cache->save($item);
        $item = $this->cache->getItem('key');

        $this->assertCount(1, $item->getPreviousTags());
    }

    /**
     * The tag must be removed whenever we remove an item. If not, when creating
     * a new item with the same key will get the same tags.
     *
     * @test
     */
    public function remove_tag_when_item_is_removed(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key')
            ->set('value');
        $item->setTags(['tag1']);

        // Save the item and then delete it
        $this->cache->save($item);
        $this->cache->deleteItem('key');

        // Create a new item (same key) (no tags)
        $item = $this->cache->getItem('key')
            ->set('value');
        $this->cache->save($item);

        // Clear the tag, The new item should not be cleared
        $this->cache->invalidateTags(['tag1']);
        $this->assertTrue(
            $this->cache->hasItem('key'),
            'Item key should be removed from the tag list when the item is removed'
        );
    }

    /**
     * @test
     */
    public function clear_pool(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key')
            ->set('value');
        $item->setTags(['tag1']);

        $this->cache->save($item);

        // Clear the pool
        $this->cache->clear();

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')
            ->set('value');
        $this->cache->save($item);
        $this->cache->invalidateTags(['tag1']);

        $this->assertTrue($this->cache->hasItem('key'), 'Tags should be removed when the pool was cleared.');
    }

    /**
     * @test
     */
    public function invalidate_tag(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key')
            ->set('value');
        $item->setTags(['tag1', 'tag2']);

        $this->cache->save($item);
        $item = $this->cache->getItem('key2')
            ->set('value');
        $item->setTags(['tag1']);

        $this->cache->save($item);

        $this->cache->invalidateTag('tag2');
        $this->assertFalse($this->cache->hasItem('key'), 'Item should be cleared when tag is invalidated');
        $this->assertTrue($this->cache->hasItem('key2'), 'Item should be cleared when tag is invalidated');

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')
            ->set('value');
        $this->cache->save($item);
        $this->cache->invalidateTags(['tag2']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');

        $this->cache->invalidateTags(['tag1']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');
    }

    /**
     * @test
     */
    public function invalidate_tags(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key')
            ->set('value');
        $item->setTags(['tag1', 'tag2']);

        $this->cache->save($item);
        $item = $this->cache->getItem('key2')
            ->set('value');
        $item->setTags(['tag1']);

        $this->cache->save($item);

        $this->cache->invalidateTags(['tag1', 'tag2']);
        $this->assertFalse($this->cache->hasItem('key'), 'Item should be cleared when tag is invalidated');
        $this->assertFalse($this->cache->hasItem('key2'), 'Item should be cleared when tag is invalidated');

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')
            ->set('value');
        $this->cache->save($item);
        $this->cache->invalidateTags(['tag1']);

        $this->assertTrue($this->cache->hasItem('key'), 'Item k list should be removed when clearing the tags');
    }

    /**
     * When an item is overwritten we need to clear tags for original item.
     *
     * @test
     */
    public function tags_are_cleaned_on_save(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $pool = $this->cache;
        $i = $pool->getItem('key')
            ->set('value');
        $pool->save($i->setTags(['foo']));
        $i = $pool->getItem('key');
        $pool->save($i->setTags(['bar']));
        $pool->invalidateTags(['foo']);
        $this->assertTrue($pool->getItem('key')->isHit());
    }
}
