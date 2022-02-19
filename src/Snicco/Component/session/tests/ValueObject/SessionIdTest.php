<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\SessionId;

use function explode;
use function strlen;

final class SessionIdTest extends TestCase
{

    /**
     * @test
     */
    public function test_createFresh_uses_new_id(): void
    {
        $session_id = SessionId::new();
        $session_id2 = SessionId::new();

        $this->assertNotSame($session_id->asString(), $session_id2->asString());
    }

    /**
     * @test
     */
    public function test_fromCookieId_with_valid_id_will_use_the_same_id(): void
    {
        $id = SessionId::new();
        $from_cookie = SessionId::fromCookieId($id->asString());

        $this->assertSame($id->asString(), $from_cookie->asString());
        $this->assertSame((string)$id, (string)$from_cookie);
        $this->assertSame($id->selector(), $from_cookie->selector());
        $this->assertSame($id->validator(), $from_cookie->validator());
    }

    /**
     * @test
     */
    public function test_fromCookie_with_invalid_id_will_create_a_new_id(): void
    {
        $from_cookie = SessionId::fromCookieId('foo|bar');

        $this->assertNotSame('foo|bar', $from_cookie->asString());
        $this->assertNotSame('foo', $from_cookie->selector());
        $this->assertNotSame('bar', $from_cookie->validator());

        $from_cookie = SessionId::fromCookieId('|');

        $this->assertNotSame('|', $from_cookie->asString());
        $this->assertNotSame('', $from_cookie->selector());
        $this->assertNotSame('', $from_cookie->validator());

        $from_cookie = SessionId::fromCookieId('');

        $this->assertNotSame('', $from_cookie->asString());
        $this->assertNotSame('', $from_cookie->selector());
        $this->assertNotSame('', $from_cookie->validator());
    }

    /**
     * @test
     */
    public function test_sameAs(): void
    {
        $id1 = SessionId::new();
        $id2 = SessionId::new();

        $this->assertFalse($id1->sameAs($id2));

        $id3 = SessionId::fromCookieId($id1->asString());
        $this->assertTrue($id1->sameAs($id3));
    }

    /**
     * @test
     */
    public function test_split_token(): void
    {
        $id = SessionId::new();

        $as_string = $id->asString();
        $this->assertStringContainsString('|', $as_string);

        $parts = explode('|', $as_string);
        $this->assertTrue(isset($parts[0]));
        $this->assertTrue(isset($parts[1]));

        $this->assertSame(24, strlen($parts[0]));
        $this->assertSame(24, strlen($parts[1]));

        $this->assertSame($parts[0], $id->selector());
        $this->assertSame($parts[1], $id->validator());

        $this->assertSame($id->selector() . '|' . $id->validator(), $id->asString());
        $this->assertSame($id->selector() . '|' . $id->validator(), (string)$id);
    }

}