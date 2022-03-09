<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\SessionConfig;
use Snicco\Component\Session\ValueObject\SessionLottery;

/**
 * @psalm-suppress MixedArgumentTypeCoercion
 *
 * @internal
 */
final class SessionConfigTest extends TestCase
{
    private array $defaults;

    protected function setUp(): void
    {
        parent::setUp();
        $this->defaults = [
            'path' => '/',
            'cookie_name' => 'my_session_cookie',
            'domain' => null,
            'http_only' => true,
            'secure' => true,
            'same_site' => 'lax',
            'idle_timeout_in_sec' => 10,
            'rotation_interval_in_sec' => 20,
            'garbage_collection_percentage' => 2,
        ];
    }

    /**
     * @test
     */
    public function test_from_defaults(): void
    {
        $config = SessionConfig::fromDefaults('my_cookie');

        $this->assertInstanceOf(SessionConfig::class, $config);
        $this->assertSame('my_cookie', $config->cookieName());
    }

    /**
     * @test
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function test_from_defaults_without_path(): void
    {
        unset($this->defaults['path']);
        $config = new SessionConfig($this->defaults);
        $this->assertSame('/', $config->cookiePath());
    }

    /**
     * @test
     */
    public function test_cookie_path(): void
    {
        $config = new SessionConfig($this->defaults);
        $this->assertSame('/', $config->cookiePath());

        $this->defaults['path'] = 'foo';
        $config = new SessionConfig($this->defaults);
        $this->assertSame('/foo', $config->cookiePath());
    }

    /**
     * @test
     */
    public function test_cookie_name(): void
    {
        $config = new SessionConfig($this->defaults);
        $this->assertSame('my_session_cookie', $config->cookieName());

        $this->expectException(InvalidArgumentException::class);
        unset($this->defaults['cookie_name']);
        new SessionConfig($this->defaults);
    }

    /**
     * @test
     */
    public function test_cookie_domain(): void
    {
        $config = new SessionConfig($this->defaults);
        $this->assertNull($config->cookieDomain());

        $this->defaults['domain'] = 'foo.com';

        $config = new SessionConfig($this->defaults);
        $this->assertSame('foo.com', $config->cookieDomain());
    }

    /**
     * @test
     */
    public function test_same_site(): void
    {
        $this->defaults['same_site'] = 'lax';
        $this->assertSame('Lax', (new SessionConfig($this->defaults))->sameSite());

        $this->defaults['same_site'] = 'Lax';
        $this->assertSame('Lax', (new SessionConfig($this->defaults))->sameSite());

        $this->defaults['same_site'] = 'LAX';
        $this->assertSame('Lax', (new SessionConfig($this->defaults))->sameSite());

        $this->defaults['same_site'] = 'Strict';
        $this->assertSame('Strict', (new SessionConfig($this->defaults))->sameSite());

        $this->defaults['same_site'] = 'none';
        $this->assertSame('None; Secure', (new SessionConfig($this->defaults))->sameSite());

        $this->expectException(InvalidArgumentException::class);

        $this->defaults['same_site'] = 'bogus';
        (new SessionConfig($this->defaults));
    }

    /**
     * @test
     */
    public function test_only_http(): void
    {
        unset($this->defaults['http_only']);
        $this->assertTrue((new SessionConfig($this->defaults))->onlyHttp());

        $this->defaults['http_only'] = false;

        $this->assertFalse((new SessionConfig($this->defaults))->onlyHttp());
    }

    /**
     * @test
     */
    public function test_only_secure(): void
    {
        unset($this->defaults['secure']);
        $this->assertTrue((new SessionConfig($this->defaults))->onlySecure());

        $this->defaults['secure'] = false;

        $this->assertFalse((new SessionConfig($this->defaults))->onlySecure());
    }

    /**
     * @test
     */
    public function test_absolute_lifetime(): void
    {
        $this->assertNull((new SessionConfig($this->defaults))->absoluteLifetimeInSec());
        $this->defaults['absolute_lifetime_in_sec'] = 10;
        $this->assertSame(10, (new SessionConfig($this->defaults))->absoluteLifetimeInSec());
    }

    /**
     * @test
     */
    public function test_idle_timeout(): void
    {
        $this->assertSame(10, (new SessionConfig($this->defaults))->idleTimeoutInSec());
        $this->defaults['idle_timeout_in_sec'] = 20;

        $this->assertSame(20, (new SessionConfig($this->defaults))->idleTimeoutInSec());

        unset($this->defaults['idle_timeout_in_sec']);

        $this->expectException(InvalidArgumentException::class);
        (new SessionConfig($this->defaults));
    }

    /**
     * @test
     */
    public function test_rotation_interval(): void
    {
        $this->defaults['rotation_interval_in_sec'] = 20;
        $this->assertSame(20, (new SessionConfig($this->defaults))->rotationInterval());
        unset($this->defaults['rotation_interval_in_sec']);
        $this->expectException(InvalidArgumentException::class);
        (new SessionConfig($this->defaults));
    }

    /**
     * @test
     */
    public function test_gc_lottery(): void
    {
        $this->defaults['garbage_collection_percentage'] = 5;
        $this->assertInstanceOf(
            SessionLottery::class,
            (new SessionConfig($this->defaults))->gcLottery()
        );

        $this->defaults['garbage_collection_percentage'] = 0;
        $this->assertInstanceOf(
            SessionLottery::class,
            (new SessionConfig($this->defaults))->gcLottery()
        );

        $this->defaults['garbage_collection_percentage'] = 100;
        $this->assertInstanceOf(
            SessionLottery::class,
            (new SessionConfig($this->defaults))->gcLottery()
        );

        $this->expectException(InvalidArgumentException::class);
        unset($this->defaults['garbage_collection_percentage']);
        (new SessionConfig($this->defaults));
    }
}
