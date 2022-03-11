<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests;

use BadMethodCallException;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Event\SessionRotated;
use Snicco\Component\Session\Exception\SessionIsLocked;
use Snicco\Component\Session\ReadWriteSession;
use Snicco\Component\Session\Serializer\JsonSerializer;
use Snicco\Component\Session\ValueObject\SerializedSession;
use Snicco\Component\Session\ValueObject\SessionId;

use function sleep;
use function time;

/**
 * @internal
 */
final class ReadWriteSessionTest extends TestCase
{
    /**
     * @test
     */
    public function the_session_is_locked_after_saving(): void
    {
        $session = $this->newSession();

        $session->put('foo', 'bar');

        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->put('baz', 'biz');
    }

    /**
     * @test
     */
    public function all_does_not_include_internal_timestamps(): void
    {
        $session = $this->newSession();

        $session->put('foo', 'bar');
        $session->put('baz', 'biz');

        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'biz',
        ], $session->all());
    }

    /**
     * @test
     */
    public function test_only(): void
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $this->assertEquals([
            'baz' => 'biz',
        ], $session->only(['baz']));
    }

    /**
     * @test
     */
    public function test_exists(): void
    {
        $session = $this->newSession();

        $session->put('foo', 'bar');
        $this->assertTrue($session->exists('foo'));

        $session->put('baz', null);
        $session->put('hulk', [
            'one' => true,
        ]);

        $this->assertTrue($session->exists('baz'));
        $this->assertTrue($session->exists(['foo', 'baz']));
        $this->assertTrue($session->exists(['hulk.one']));

        $this->assertFalse($session->exists(['foo', 'baz', 'bogus']));
        $this->assertFalse($session->exists(['hulk.two']));
        $this->assertFalse($session->exists('bogus'));
    }

    /**
     * @test
     */
    public function test_missing(): void
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', null);
        $session->put('hulk', [
            'one' => true,
        ]);

        $this->assertTrue($session->missing('bogus'));
        $this->assertTrue($session->missing(['foo', 'baz', 'bogus']));
        $this->assertTrue($session->missing(['hulk.two']));

        $this->assertFalse($session->missing('foo'));
        $this->assertFalse($session->missing('baz'));
        $this->assertFalse($session->missing(['foo', 'baz']));
        $this->assertFalse($session->missing(['hulk.one']));
    }

    /**
     * @test
     */
    public function test_has(): void
    {
        $session = $this->newSession();
        $session->put('foo', null);
        $session->put('bar', 'baz');

        $this->assertTrue($session->has('bar'));
        $this->assertFalse($session->has('foo'));
        $this->assertFalse($session->has('baz'));
    }

    /**
     * @test
     */
    public function test_get(): void
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');

        $this->assertSame('bar', $session->get('foo', 'default'));
        $this->assertSame('default', $session->get('boo', 'default'));
    }

    /**
     * @test
     */
    public function test_has_old_input(): void
    {
        $session = $this->newSession();

        $this->assertFalse($session->hasOldInput());

        $session->put('_old_input', [
            'foo' => 'bar',
            'bar' => 'baz',
            'boo' => null,
        ]);

        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertTrue($session->hasOldInput('bar'));
        $this->assertFalse($session->hasOldInput('biz'));
        $this->assertFalse($session->hasOldInput('boo'));
    }

    /**
     * @test
     */
    public function test_get_old_input(): void
    {
        $session = $this->newSession();

        $this->assertSame([], $session->oldInput());

        $session->put('_old_input', [
            'foo' => 'bar',
            'bar' => 'baz',
            'boo' => null,
        ]);

        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'boo' => null,
        ], $session->oldInput());

        $this->assertSame('bar', $session->oldInput('foo'));
        $this->assertSame('baz', $session->oldInput('bar'));
        $this->assertNull($session->oldInput('boo'));

        $this->assertNull($session->oldInput('boo', 'default'));
        $this->assertSame('default', $session->oldInput('bogus', 'default'));
    }

    /**
     * @test
     */
    public function test_replace(): void
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $session->replace([
            'foo' => 'baz',
        ]);
        $this->assertSame('baz', $session->get('foo'));
        $this->assertSame('biz', $session->get('baz'));
    }

    /**
     * @test
     */
    public function test_put_if_missing(): void
    {
        $session = $this->newSession();

        $session->putIfMissing('foo', fn (): string => 'bar');
        $this->assertSame('bar', $session->get('foo'));

        $session->put('baz', 'biz');

        $session->putIfMissing('baz', function (): void {
            $this->fail('This should not have been called');
        });

        $this->assertSame('biz', $session->get('baz'));
    }

    /**
     * @test
     */
    public function test_push(): void
    {
        $session = $this->newSession();

        $session->put('foo', ['bar']);
        $session->push('foo', 'bar');
        $session->push('foo', [
            'baz' => 'biz',
        ]);

        $this->assertSame([
            'bar', 'bar', [
                'baz' => 'biz',
            ], ], $session->get('foo'));

        $session->push('int', 'foo');
        $this->assertSame(['foo'], $session->get('int'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value for key [int] is not an array.');

        $session->put('int', 1);
        $session->push('int', 'foo');
    }

    /**
     * @test
     */
    public function test_increment(): void
    {
        $session = $this->newSession();

        $session->put('foo', 5);
        $session->increment('foo');
        $this->assertSame(6, $session->get('foo'));

        $session->increment('foo', 4);
        $this->assertSame(10, $session->get('foo'));

        $this->assertNull($session->get('bar'));
        $session->increment('bar');
        $this->assertSame(1, $session->get('bar'));
    }

    /**
     * @test
     */
    public function test_increment_throws_if_current_is_not_integer(): void
    {
        $session = $this->newSession();

        $session->put('foo', 'bar');
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Current value for key [foo] is not an integer.');
        $session->increment('foo');
    }

    /**
     * @test
     */
    public function test_decrement(): void
    {
        $session = $this->newSession();

        $session->put('foo', 5);
        $session->decrement('foo');
        $this->assertSame(4, $session->get('foo'));

        $session->decrement('foo', 4);
        $this->assertSame(0, $session->get('foo'));

        $this->assertNull($session->get('bar'));
        $session->decrement('bar');
        $this->assertSame(-1, $session->get('bar'));
    }

    /**
     * @test
     */
    public function test_flash(): void
    {
        $session = $this->newSession();
        $session->flash('foo', 'bar');
        $session->flash('bar', 0);
        $session->flash('baz');

        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame(0, $session->get('bar'));
        $this->assertTrue($session->get('baz'));

        $session->saveUsing($driver = new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame(0, $session->get('bar'));

        $session = $this->reloadSession($session, $driver);
        $session->saveUsing($driver, new JsonSerializer(), 'v', time());

        $this->assertFalse($session->has('foo'));
        $this->assertNull($session->get('foo'));
    }

    /**
     * @test
     */
    public function testflash_now(): void
    {
        $session = $this->newSession();
        $session->flashNow('foo', 'bar');
        $session->flashNow('bar', 0);

        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame(0, $session->get('bar'));

        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->assertFalse($session->has('foo'));
        $this->assertNull($session->get('foo'));
    }

    /**
     * @test
     *
     * @psalm-suppress MixedArgument
     */
    public function test_reflash(): void
    {
        $session = $this->newSession();
        $session->flash('foo', 'bar');
        $session->put('_flash.old', ['foo']);
        $session->reflash();
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new'), true));
        $this->assertFalse(array_search('foo', $session->get('_flash.old'), true));
    }

    /**
     * @test
     *
     * @psalm-suppress MixedArgument
     */
    public function test_reflash_with_now(): void
    {
        $session = $this->newSession();
        $session->flashNow('foo', 'bar');
        $session->reflash();
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new'), true));
        $this->assertFalse(array_search('foo', $session->get('_flash.old'), true));
    }

    /**
     * @test
     */
    public function test_flash_input(): void
    {
        $session = $this->newSession();
        $session->put('boom', 'baz');
        $session->flashInput([
            'foo' => 'bar',
            'bar' => 0,
        ]);

        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertSame('bar', $session->oldInput('foo'));
        $this->assertSame(0, $session->oldInput('bar'));
        $this->assertFalse($session->hasOldInput('boom'));

        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertSame('bar', $session->oldInput('foo'));
        $this->assertSame(0, $session->oldInput('bar'));
        $this->assertFalse($session->hasOldInput('boom'));
    }

    /**
     * @test
     *
     * @psalm-suppress MixedArgument
     */
    public function test_keep(): void
    {
        $session = $this->newSession();
        $session->flash('foo', 'bar');
        $session->put('fu', 'baz');
        $session->put('_flash.old', ['qu']);
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new'), true));
        $this->assertFalse(array_search('fu', $session->get('_flash.new'), true));
        $session->keep(['fu', 'qu']);
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new'), true));
        $this->assertNotFalse(array_search('fu', $session->get('_flash.new'), true));
        $this->assertNotFalse(array_search('qu', $session->get('_flash.new'), true));
        $this->assertFalse(array_search('qu', $session->get('_flash.old'), true));
    }

    /**
     * @test
     */
    public function test_remove(): void
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');

        $session->remove('foo');

        $this->assertSame('biz', $session->get('baz'));
        $this->assertFalse($session->has('foo'));
    }

    /**
     * @test
     */
    public function test_forget(): void
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $session->put('boo', [
            'boom',
            'bang' => 'bam',
        ]);

        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame('biz', $session->get('baz'));
        $this->assertSame([
            'boom',
            'bang' => 'bam',
        ], $session->get('boo'));
        $this->assertSame('bam', $session->get('boo.bang'));

        $session->forget('foo');
        $session->forget('boo.bang');

        $this->assertFalse($session->exists('foo'));
        $this->assertTrue($session->exists('baz'));
        $this->assertTrue($session->exists('boo'));

        $this->assertSame(['boom'], $session->get('boo'));
    }

    /**
     * @test
     */
    public function test_flush(): void
    {
        $session = $this->newSession();
        $session->put('foo', 'bar');
        $session->put('baz', 'biz');
        $session->put('boo', [
            'boom',
            'bang' => 'bam',
        ]);

        $session->flush();

        $this->assertSame([], $session->all());
    }

    /**
     * @test
     */
    public function test_boolean(): void
    {
        $session = $this->newSession();
        $session->put('foo', 1);
        $session->put('bar', '1');
        $session->put('baz', 'on');
        $session->put('biz', 'yes');
        $session->put('boo', true);
        $session->put('bam', 'true');
        $session->put('bogus', 'bogus');
        $session->put('false_word', 'false');
        $session->put('off', 'off');
        $session->put('no', 'no');
        $session->put('false_bool', false);

        $this->assertTrue($session->boolean('foo'));
        $this->assertTrue($session->boolean('bar'));
        $this->assertTrue($session->boolean('baz'));
        $this->assertTrue($session->boolean('biz'));
        $this->assertTrue($session->boolean('boo'));
        $this->assertTrue($session->boolean('bam'));
        $this->assertFalse($session->boolean('bogus'));
        $this->assertFalse($session->boolean('false_word'));
        $this->assertFalse($session->boolean('off'));
        $this->assertFalse($session->boolean('no'));
        $this->assertFalse($session->boolean('false_bool'));
    }

    /**
     * @test
     */
    public function test_timestamp_values_stay_the_same(): void
    {
        $id = SessionId::new();

        $session = new ReadWriteSession($id, [], time());

        $created_at = $session->createdAt();
        $last_rotated = $session->lastRotation();
        $last_activity = $session->lastActivity();

        $this->assertSame(time(), $created_at);
        $this->assertSame(time(), $last_rotated);
        $this->assertSame(time(), $last_activity);

        $driver = new InMemoryDriver();
        $session->saveUsing($driver, new JsonSerializer(), 'v', time());

        sleep(1);

        $session = $this->reloadSession($session, $driver);

        $this->assertSame($created_at, $session->createdAt());
        $this->assertSame($last_rotated, $session->lastActivity());
        $this->assertSame($last_activity, $session->lastRotation());
    }

    /**
     * @test
     */
    public function test_last_activity_is_updated_on_save(): void
    {
        $id = SessionId::new();

        $session = new ReadWriteSession($id, [], time());

        $last_activity = $session->lastActivity();

        $this->assertSame(time(), $last_activity);

        sleep(1);

        $driver = new InMemoryDriver();
        $session->saveUsing($driver, new JsonSerializer(), 'v', time());

        $session = $this->reloadSession($session, $driver);

        $this->assertSame($last_activity + 1, $session->lastActivity());
    }

    /**
     * @test
     */
    public function test_is_dirty_after_attribute_change(): void
    {
        $session = $this->newPersistedSession();

        $this->assertFalse($session->isDirty());

        $session->put('foo', 'bar');

        $this->assertTrue($session->isDirty());
    }

    /**
     * @test
     */
    public function test_is_dirty_after_rotating(): void
    {
        $session = $this->newPersistedSession();
        $this->assertFalse($session->isDirty());

        $session->rotate();

        $this->assertTrue($session->isDirty());
    }

    /**
     * @test
     */
    public function test_is_dirty_after_invalidating(): void
    {
        $session = $this->newPersistedSession();
        $this->assertFalse($session->isDirty());

        $session->invalidate();

        $this->assertTrue($session->isDirty());
    }

    /**
     * @test
     */
    public function test_is_dirty_with_flash_data(): void
    {
        $old_session = $this->newPersistedSession();
        $old_session->flash('foo', 'bar');

        $this->assertTrue($old_session->isDirty());

        $driver = new InMemoryDriver();

        $old_session->saveUsing($driver, new JsonSerializer(), 'v', time());

        $new_session = $this->reloadSession($old_session, $driver);

        $this->assertTrue($new_session->isDirty());

        $new_session->saveUsing($driver, $serializer = new JsonSerializer(), 'v', time());

        $data = $driver->read($old_session->id()->selector());
        $new_session = new ReadWriteSession($new_session->id(), $serializer->deserialize($data->data()), time());

        $this->assertFalse($new_session->isDirty());
    }

    /**
     * @test
     */
    public function test_is_always_dirty_after_creating(): void
    {
        $driver = new InMemoryDriver();
        $session = $this->newSession();
        $this->assertTrue($session->isDirty());

        $session->saveUsing($driver, new JsonSerializer(), 'v', time());

        $session = $this->reloadSession($session, $driver);
        $this->assertFalse($session->isDirty());
    }

    /**
     * @test
     */
    public function a_dirty_session_is_saved_to_the_driver(): void
    {
        $spy_driver = new SpyDriver();
        $session = $this->newPersistedSession();

        $session->put('foo', 'bar');

        $session->saveUsing($spy_driver, new JsonSerializer(), 'v', time());

        $calls = $spy_driver->written;
        $this->assertCount(1, $calls);

        $calls = $spy_driver->touched;
        $this->assertCount(0, $calls);
    }

    /**
     * @test
     */
    public function a_clean_session_is_only_touched(): void
    {
        $spy_driver = new SpyDriver();
        $session = $this->newPersistedSession();

        $session->saveUsing($spy_driver, new JsonSerializer(), 'v', time());

        $calls = $spy_driver->written;
        $this->assertCount(0, $calls);

        $calls = $spy_driver->touched;
        $this->assertCount(1, $calls);
    }

    /**
     * @test
     */
    public function the_session_id_is_changed_immediately_after_rotating(): void
    {
        $session = $this->newSession();
        $old_id = $session->id();

        $session->rotate();

        $new_id = $session->id();
        $this->assertFalse($old_id->sameAs($new_id));
    }

    /**
     * @test
     */
    public function the_session_id_is_changed_immediately_after_invalidating(): void
    {
        $session = $this->newSession();
        $old_id = $session->id();

        $session->invalidate();

        $new_id = $session->id();
        $this->assertFalse($old_id->sameAs($new_id));
    }

    /**
     * @test
     */
    public function test_session_rotated_event_is_stored(): void
    {
        $session = $this->newSession();
        $this->assertEmpty($session->releaseEvents());

        $session->rotate();

        $events = $session->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertTrue(isset($events[0]));
        $this->assertInstanceOf(SessionRotated::class, $events[0]);

        $this->assertEmpty($session->releaseEvents());
    }

    /**
     * @test
     */
    public function test_invalidate_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->invalidate();
    }

    /**
     * @test
     */
    public function test_rotate_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->rotate();
    }

    /**
     * @test
     */
    public function test_forget_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->forget(['foo']);
    }

    /**
     * @test
     */
    public function test_put_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->put('foo', 'bar');
    }

    /**
     * @test
     */
    public function test_replace_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'validator', time());

        $this->expectException(SessionIsLocked::class);

        $session->replace([
            'foo' => 'bar',
        ]);
    }

    /**
     * @test
     */
    public function test_put_if_missing_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->putIfMissing('foo', fn (): string => 'bar');
    }

    /**
     * @test
     */
    public function test_decrement_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->decrement('foo', 1);
    }

    /**
     * @test
     */
    public function test_increment_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->increment('foo', 1);
    }

    /**
     * @test
     */
    public function test_push_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->push('foo', 'bar');
    }

    /**
     * @test
     */
    public function test_flash_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->flash('foo', 'bar');
    }

    /**
     * @test
     */
    public function test_flash_now_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->flashNow('foo', 'bar');
    }

    /**
     * @test
     */
    public function test_flash_input_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->flashInput([
            'foo' => 'bar',
        ]);
    }

    /**
     * @test
     */
    public function test_reflash_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->reflash();
    }

    /**
     * @test
     */
    public function test_keep_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->keep(['foo']);
    }

    /**
     * @test
     */
    public function test_flush_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->flush();
    }

    /**
     * @test
     */
    public function test_remove_throws_after_saving(): void
    {
        $session = $this->newSession();
        $session->saveUsing(new InMemoryDriver(), new JsonSerializer(), 'v', time());

        $this->expectException(SessionIsLocked::class);

        $session->remove('foo');
    }

    /**
     * @test
     */
    public function test_bad_created_at_timestamp_throws(): void
    {
        $driver = new InMemoryDriver();
        $session = $this->newPersistedSession(null, [], $driver);

        $this->assertNotEmpty($session->createdAt());
        $session->remove('_sniccowp.timestamps.created_at');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('corrupted');
        $session->createdAt();
    }

    /**
     * @test
     */
    public function test_bad_rotated_at_timestamp_throws(): void
    {
        $driver = new InMemoryDriver();
        $session = $this->newPersistedSession(null, [], $driver);

        $this->assertNotEmpty($session->lastRotation());
        $session->remove('_sniccowp.timestamps.last_rotated');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('corrupted');
        $session->lastRotation();
    }

    /**
     * @test
     */
    public function test_user_id(): void
    {
        $session = $this->newSession();

        $this->assertNull($session->userId());

        $session->setUserId(1);
        $this->assertSame(1, $session->userId());

        $session->setUserId('foo');
        $this->assertSame('foo', $session->userId());
    }

    /**
     * @test
     */
    public function test_user_id_is_flushed(): void
    {
        $session = $this->newSession();
        $session->setUserId(1);

        $this->assertSame(1, $session->userId());

        $session->flush();
        $this->assertNull($session->userId());

        $session = $this->newSession();
        $session->setUserId(1);

        $session->invalidate();
        $this->assertNull($session->userId());
    }

    /**
     * @test
     */
    public function test_user_id_is_saved_to_driver(): void
    {
        $session = $this->newSession();
        $session->setUserId(1);

        $driver = new InMemoryDriver();

        $session->saveUsing($driver, new JsonSerializer(), 'foo_validator', time());

        $all = $driver->all();

        $this->assertTrue(isset($all[$session->id()->selector()]));

        $serialized_session = $driver->read($session->id()->selector());
        $this->assertSame(1, $serialized_session->userId());
    }

    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exception_for_non_string_non_int_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string or integer');
        $this->newSession()
            ->setUserId(true);
    }

    /**
     * @test
     */
    public function test_exception_for_getting_non_string_non_int_user_id(): void
    {
        $session = $this->newSession();
        $session->put('_user_id', true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string or integer');

        $session->userId();
    }

    /**
     * @test
     */
    public function test_is_new(): void
    {
        $driver = new InMemoryDriver();

        $session = $this->newSession();
        $this->assertTrue($session->isNew());

        $session->saveUsing($driver, new JsonSerializer(), 'hashed_val', time());

        $session = $this->reloadSession($session, $driver);
        $this->assertFalse($session->isNew());
    }

    private function newSession(string $id = null, array $data = []): ReadWriteSession
    {
        $id = $id ? SessionId::fromCookieId($id) : SessionId::new();

        return new ReadWriteSession($id, $data, time());
    }

    private function reloadSession(ReadWriteSession $session, SessionDriver $driver): ReadWriteSession
    {
        $data = $driver->read($session->id()->selector());

        return new ReadWriteSession(
            $session->id(),
            (new JsonSerializer())->deserialize($data->data()),
            $data->lastActivity()
        );
    }

    private function newPersistedSession(
        string $id = null,
        array $data = [],
        ?InMemoryDriver $driver = null
    ): ReadWriteSession {
        $session = $this->newSession($id, $data);
        $driver ??= new InMemoryDriver();
        $session->saveUsing($driver, new JsonSerializer(), 'v', time());

        return $this->reloadSession($session, $driver);
    }
}

final class SpyDriver implements SessionDriver
{
    public array $written = [];

    public array $touched = [];

    public function read(string $selector): SerializedSession
    {
        throw new BadMethodCallException('read not implemented');
    }

    public function write(string $selector, SerializedSession $session): void
    {
        $this->written[$selector] = $session;
    }

    public function destroy(array $selectors): void
    {
        throw new BadMethodCallException('destroy not implemented');
    }

    public function gc(int $seconds_without_activity): void
    {
        throw new BadMethodCallException('gc not implemented');
    }

    public function touch(string $selector, int $current_timestamp): void
    {
        $this->touched[$selector] = $current_timestamp;
    }
}
