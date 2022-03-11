<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\MutableSession;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\Tests\fixtures\SessionHelpers;
use Snicco\Component\Session\ValueObject\ReadOnlySession;

use function time;

/**
 * @internal
 */
final class ReadOnlySessionTest extends TestCase
{
    use SessionHelpers;

    /**
     * @test
     */
    public function test_is_immutable(): void
    {
        $session = $this->newSession();
        $store = ReadOnlySession::fromSession($session);

        $this->assertInstanceOf(ImmutableSession::class, $store);
        $this->assertNotInstanceOf(Session::class, $store);
        $this->assertNotInstanceOf(MutableSession::class, $store);
    }

    /**
     * @test
     */
    public function test_all(): void
    {
        $session = $this->newSession(null, [
            'foo' => 'bar',
        ]);
        $store = ReadOnlySession::fromSession($session);
        $this->assertSame([
            'foo' => 'bar',
        ], $store->all());
    }

    /**
     * @test
     */
    public function test_boolean(): void
    {
        $session = $this->newSession(null, [
            'foo' => 1,
            'bar' => 0,
        ]);
        $store = ReadOnlySession::fromSession($session);
        $this->assertTrue($store->boolean('foo'));
        $this->assertFalse($store->boolean('bar'));
    }

    /**
     * @test
     */
    public function test_created_at(): void
    {
        $session = $this->newSession(null, [
            'foo' => 1,
            'bar' => 0,
        ]);
        $store = ReadOnlySession::fromSession($session);
        $this->assertEqualsWithDelta(time(), $store->createdAt(), 1);
    }

    /**
     * @test
     */
    public function test_exists_has(): void
    {
        $session = $this->newSession(null, [
            'foo' => false,
            'bar' => null,
            'baz' => '',
        ]);
        $store = ReadOnlySession::fromSession($session);

        $this->assertTrue($store->exists('foo'));
        $this->assertTrue($store->exists('baz'));
        $this->assertTrue($store->exists('bar'));

        $this->assertTrue($store->has('foo'));
        $this->assertFalse($store->has('bar'));
        $this->assertTrue($store->has('baz'));
    }

    /**
     * @test
     */
    public function test_has_old_input(): void
    {
        $session = $this->newSession();
        $session->flashInput([
            'foo' => 'bar',
        ]);

        $store = ReadOnlySession::fromSession($session);

        $this->assertTrue($store->hasOldInput('foo'));
        $this->assertFalse($store->hasOldInput('bar'));
    }

    /**
     * @test
     */
    public function test_id(): void
    {
        $session = $this->newSession();

        $store = ReadOnlySession::fromSession($session);
        $this->assertEquals($session->id(), $store->id());
    }

    /**
     * @test
     */
    public function test_last_activity(): void
    {
        $session = $this->newSession();

        $store = ReadOnlySession::fromSession($session);
        $this->assertSame($session->lastActivity(), $store->lastActivity());
    }

    /**
     * @test
     */
    public function test_last_rotation(): void
    {
        $session = $this->newSession();

        $store = ReadOnlySession::fromSession($session);
        $this->assertSame($session->lastRotation(), $store->lastRotation());
    }

    /**
     * @test
     */
    public function test_missing(): void
    {
        $session = $this->newSession(null, [
            'foo' => false,
            'bar' => null,
            'baz' => '',
        ]);
        $store = ReadOnlySession::fromSession($session);

        $this->assertFalse($store->missing('foo'));
        $this->assertFalse($store->missing('baz'));
        $this->assertFalse($store->missing('bar'));
        $this->assertTrue($store->missing('bogus'));
    }

    /**
     * @test
     */
    public function test_old_input(): void
    {
        $session = $this->newSession();
        $session->flashInput([
            'foo' => 'bar',
        ]);

        $store = ReadOnlySession::fromSession($session);

        $this->assertSame('bar', $store->oldInput('foo'));
    }

    /**
     * @test
     */
    public function test_only(): void
    {
        $session = $this->newSession(null, [
            'foo' => false,
            'bar' => null,
            'baz' => '',
        ]);
        $store = ReadOnlySession::fromSession($session);

        $this->assertSame([
            'foo' => false,
            'bar' => null,
        ], $store->only(['foo', 'bar']));
    }

    /**
     * @test
     */
    public function test_user_id(): void
    {
        $session = $this->newSession();
        $session->setUserId(10);

        $store = ReadOnlySession::fromSession($session);
        $this->assertSame(10, $store->userId());
    }

    /**
     * @test
     */
    public function test_is_new(): void
    {
        $session = $this->newSession();
        $this->assertTrue(ReadOnlySession::fromSession($session)->isNew());
    }
}
