<?php

declare(strict_types=1);

namespace Tests\Session\unit;

use Snicco\Session\ImmutableSession;
use Tests\Codeception\shared\UnitTest;
use Snicco\Session\Contracts\SessionInterface;
use Tests\Codeception\shared\helpers\SessionHelpers;
use Snicco\Session\Contracts\MutableSessionInterface;
use Snicco\Session\Contracts\ImmutableSessionInterface;

final class ImmutableStoreTest extends UnitTest
{
    
    use SessionHelpers;
    
    /** @test */
    public function testImmutableStore()
    {
        $session = $this->newSession();
        
        $store = ImmutableSession::fromSession($session);
        
        $this->assertInstanceOf(ImmutableSessionInterface::class, $store);
        $this->assertNotInstanceOf(SessionInterface::class, $store);
        $this->assertNotInstanceOf(MutableSessionInterface::class, $store);
    }
    
}