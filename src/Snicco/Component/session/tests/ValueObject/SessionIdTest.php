<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\ValueObject\SessionId;

final class SessionIdTest extends TestCase
{

    /**
     * @test
     */
    public function testConstructWithoutIdCreatesNewId(): void
    {
        SessionId::$token_strength = 20;
        $session_id = SessionId::createFresh();
        $id_as_string = $session_id->asString();
        $this->assertSame(40, strlen($id_as_string));

        $session_id2 = SessionId::createFresh();
        $id_as_string2 = $session_id2->asString();
        $this->assertSame(40, strlen($id_as_string2));
    }

    /**
     * @test
     */
    public function testConstructWithIdStaysSameId(): void
    {
        $id_as_string = SessionId::createFresh()->asString();

        $session_id = SessionId::fromCookieId($id_as_string);

        $this->assertSame($id_as_string, $session_id->asString());
        $this->assertSame($id_as_string, (string)$session_id);
    }

    /**
     * @test
     */
    public function testAsHash(): void
    {
        $id_as_string = SessionId::createFresh()->asString();

        $session_id = SessionId::fromCookieId($id_as_string);

        $this->assertSame($id_as_string, $session_id->asString());
        $this->assertNotSame($id_as_string, $session_id->asHash());
    }

    /**
     * @test
     */
    public function testSameAs(): void
    {
        $id1 = SessionId::createFresh();
        $id2 = SessionId::createFresh();

        $this->assertFalse($id1->sameAs($id2));

        $id3 = SessionId::fromCookieId($id1->asString());
        $this->assertTrue($id1->sameAs($id3));
    }

}