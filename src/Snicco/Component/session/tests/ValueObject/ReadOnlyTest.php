<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\MutableSession;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\ValueObject\ReadOnly;
use Snicco\Component\Session\Tests\fixtures\SessionHelpers;

final class ReadOnlyTest extends TestCase
{
    
    use SessionHelpers;
    
    /** @test */
    public function testImmutableStore()
    {
        $session = $this->newSession();
        
        $store = ReadOnly::fromSession($session);
        
        $this->assertInstanceOf(ImmutableSession::class, $store);
        $this->assertNotInstanceOf(Session::class, $store);
        $this->assertNotInstanceOf(MutableSession::class, $store);
    }
    
}