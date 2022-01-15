<?php

declare(strict_types=1);

namespace Tests\Session\unit;

use Tests\Codeception\shared\UnitTest;
use Snicco\Session\ValueObjects\SessionId;

final class SessionIdTest extends UnitTest
{
    
    /** @test */
    public function testConstructWithoutIdCreatesNewId()
    {
        SessionId::$token_strength = 20;
        $session_id = SessionId::createFresh();
        $id_as_string = $session_id->asString();
        $this->assertSame(40, strlen($id_as_string));
        
        $session_id2 = SessionId::createFresh();
        $id_as_string2 = $session_id2->asString();
        $this->assertSame(40, strlen($id_as_string2));
    }
    
    /** @test */
    public function testConstructWithIdStaysSameId()
    {
        $id_as_string = SessionId::createFresh()->asString();
        
        $session_id = SessionId::fromCookieId($id_as_string);
        
        $this->assertSame($id_as_string, $session_id->asString());
    }
    
    /** @test */
    public function testAsHash()
    {
        $id_as_string = SessionId::createFresh()->asString();
        
        $session_id = SessionId::fromCookieId($id_as_string);
        
        $this->assertSame($id_as_string, $session_id->asString());
        $this->assertNotSame($id_as_string, $session_id->asHash());
    }
    
    /** @test */
    public function testSameAs()
    {
        $id1 = SessionId::createFresh();
        $id2 = SessionId::createFresh();
        
        $this->assertFalse($id1->sameAs($id2));
        
        $id3 = SessionId::fromCookieId($id1->asString());
        $this->assertTrue($id1->sameAs($id3));
    }
    
}